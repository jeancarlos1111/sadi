<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\AsientoContable;
use App\Services\FormulaEvaluator;
use Exception;
use PDO;

class PlanillaNominaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'planilla_nomina';
    }

    /**
     * Motor de cálculo central de la Nómina.
     */
    public function generar(int $idNomina, string $periodo, string $fechaEmision): bool
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            // 1. Insertar Cabecera de Planilla
            $stmtPlanilla = $db->prepare("
                INSERT INTO planilla_nomina (nomina_cod_nomina, fecha_emision, periodo)
                VALUES (?, ?, ?)
            ");
            $stmtPlanilla->execute([$idNomina, $fechaEmision, $periodo]);
            $idPlanilla = (int)$db->lastInsertId();

            // 2. Obtener trabajadores activos
            $fichas = $db->prepare("
                SELECT f.cod_ficha, f.sueldo_basico
                FROM ficha f
                WHERE f.nomina_cod_nomina = ? AND f.eliminado = 0
            ");
            $fichas->execute([$idNomina]);
            $trabajadores = $fichas->fetchAll(PDO::FETCH_ASSOC);

            if (empty($trabajadores)) {
                throw new Exception("No hay trabajadores activos en esta nómina.");
            }

            // 3. Obtener Conceptos
            $conceptRepo = new ConceptoNominaRepository();
            $conceptos = $conceptRepo->all();

            $totalAsignacionesGlobal = 0.0;
            $totalDeduccionesGlobal = 0.0;

            $stmtDetallePlanilla = $db->prepare("
                INSERT INTO detalle_planilla_nomina (id_planilla, cod_ficha, neto_trabajador)
                VALUES (?, ?, ?)
            ");

            $stmtDetalleRecibo = $db->prepare("
                INSERT INTO detalle_recibo_concepto (id_detalle_planilla, id_concepto, monto_calculado)
                VALUES (?, ?, ?)
            ");

            // 4. Bucle principal
            foreach ($trabajadores as $trabajador) {
                $sueldoBase = (float)$trabajador['sueldo_basico'];
                $idFicha = (int)$trabajador['cod_ficha'];

                $totalAsignacionTrab = 0.0;
                $totalDeduccionTrab = 0.0;

                $stmtDetallePlanilla->execute([$idPlanilla, $idFicha, 0]);
                $idDetalle = (int)$db->lastInsertId();

                foreach ($conceptos as $concepto) {
                    $montoCalculado = 0.0;

                    if (!empty($concepto->formulaExpr)) {
                        try {
                            $evaluator = (new FormulaEvaluator())->setVariables([
                                'SUELDO'         => $sueldoBase,
                                'TOTAL_ASIG'     => $totalAsignacionTrab,
                                'SALARIO_MINIMO' => 0.0,
                                'CESTATICKET'    => 0.0,
                            ]);
                            $montoCalculado = max(0.0, $evaluator->evaluate($concepto->formulaExpr));
                        } catch (\InvalidArgumentException $e) {
                            $montoCalculado = 0.0;
                        }
                    } elseif ($concepto->esPorcentaje) {
                        $montoCalculado = $sueldoBase * ($concepto->formulaValor / 100);
                    } else {
                        $montoCalculado = $concepto->formulaValor;
                    }

                    if ($montoCalculado > 0) {
                        if ($concepto->tipo === 'A') {
                            $totalAsignacionTrab += $montoCalculado;
                        } else {
                            $totalDeduccionTrab += $montoCalculado;
                        }
                        $stmtDetalleRecibo->execute([$idDetalle, $concepto->id, $montoCalculado]);
                    }
                }

                $netoTrabajador = $totalAsignacionTrab - $totalDeduccionTrab;
                if ($netoTrabajador < 0) {
                    $netoTrabajador = 0;
                }

                $db->prepare("UPDATE detalle_planilla_nomina SET neto_trabajador = ? WHERE id_detalle_planilla = ?")
                   ->execute([$netoTrabajador, $idDetalle]);

                $totalAsignacionesGlobal += $totalAsignacionTrab;
                $totalDeduccionesGlobal += $totalDeduccionTrab;
            }

            $totalNetoGlobal = $totalAsignacionesGlobal - $totalDeduccionesGlobal;

            // 5. Totales Cabecera
            $db->prepare("
                UPDATE planilla_nomina 
                SET monto_total_asignaciones = ?, monto_total_deducciones = ?, monto_total_neto = ? 
                WHERE id_planilla = ?
            ")->execute([$totalAsignacionesGlobal, $totalDeduccionesGlobal, $totalNetoGlobal, $idPlanilla]);

            // 6. Impacto Transaccional 1: PRESUPUESTO
            $db->prepare("
                UPDATE presupuesto_gastos 
                SET monto_comprometido = monto_comprometido + ?,
                    monto_causado = monto_causado + ?
                WHERE id_codigo_plan_unico = 4
            ")->execute([$totalAsignacionesGlobal, $totalAsignacionesGlobal]);

            // Asiento contable del Causado
            $convRepo = new ConvertidorCuentaRepository();
            $idPartidaNomina = 4;
            $idCuentaGasto = $convRepo->getCuentaContableId($idPartidaNomina, 'NOMINA_ASIGNACION');
            $idCuentaPasivo = $convRepo->getCuentaContableId($idPartidaNomina, 'NOMINA_DEDUCCION');

            $asientoDetalles = [];
            $asientoDetalles[] = ['id_cuenta' => $idCuentaGasto ?: 6, 'tipo' => 'D', 'monto' => $totalAsignacionesGlobal];
            $asientoDetalles[] = ['id_cuenta' => $idCuentaPasivo ?: 3, 'tipo' => 'H', 'monto' => $totalAsignacionesGlobal];

            AsientoContable::registrarDesdeTransaccion(
                $fechaEmision,
                "Causado de Nómina - Planilla N° {$idPlanilla}",
                $asientoDetalles
            );

            // 7. Impacto Transaccional 2: TESORERÍA
            $conceptoPago = "Pago de {$periodo}. Planilla N° {$idPlanilla}";
            $db->prepare("
                INSERT INTO solicitud_pago (fecha_solicitud_pago, concepto_solicitud_pago, monto_pagar_solicitud_pago)
                VALUES (?, ?, ?)
            ")->execute([$fechaEmision, $conceptoPago, $totalNetoGlobal]);

            $db->commit();

            return true;

        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }

    public function all(): array
    {
        $db = $this->getPdo();

        return $db->query("
            SELECT p.*, n.denom as nombre_nomina 
            FROM planilla_nomina p
            JOIN nomina n ON p.nomina_cod_nomina = n.cod_nomina
            ORDER BY p.id_planilla DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}
