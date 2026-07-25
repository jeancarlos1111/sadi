<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use App\Repositories\AsientoContableRepository;
use Exception;
use PDO;

class CierreContableService
{
    private AsientoContableRepository $asientoRepo;

    public function __construct()
    {
        $this->asientoRepo = new AsientoContableRepository(Connection::getInstance());
    }

    /**
     * Obtiene el resumen de saldos de las cuentas nominales (Clase 4 y 5).
     */
    public function obtenerResumenCierre(string $anio): array
    {
        $db = Connection::getInstance();

        // Saldo total de Ingresos (Clase 4)
        $stmtIngresos = $db->prepare("
            SELECT COALESCE(SUM(mc.monto_mc * CASE WHEN mc.tipo_operacion_mc = 'H' THEN 1 ELSE -1 END), 0) as saldo
            FROM movimiento_contable mc
            JOIN comprobante_diario cd ON mc.id_comprobante_diario = cd.id_comprobante_diario
            JOIN cuenta_contable cc ON mc.id_cuenta_contable = cc.id_cuenta_contable
            WHERE cd.fecha_comprobante LIKE ? AND cc.codigo_cuenta LIKE '4.%' AND cd.eliminado = false
        ");
        $stmtIngresos->execute(["{$anio}-%"]);
        $totalIngresos = (float)$stmtIngresos->fetchColumn();

        // Saldo total de Gastos (Clase 5)
        // Usamos Debe - Haber porque la naturaleza de gastos es deudora
        $stmtGastos = $db->prepare("
            SELECT COALESCE(SUM(mc.monto_mc * CASE WHEN mc.tipo_operacion_mc = 'D' THEN 1 ELSE -1 END), 0) as saldo
            FROM movimiento_contable mc
            JOIN comprobante_diario cd ON mc.id_comprobante_diario = cd.id_comprobante_diario
            JOIN cuenta_contable cc ON mc.id_cuenta_contable = cc.id_cuenta_contable
            WHERE cd.fecha_comprobante LIKE ? AND cc.codigo_cuenta LIKE '5.%' AND cd.eliminado = false
        ");
        $stmtGastos->execute(["{$anio}-%"]);
        $totalGastos = (float)$stmtGastos->fetchColumn();

        $resultado = $totalIngresos - $totalGastos;

        return [
            'anio' => $anio,
            'total_ingresos' => $totalIngresos,
            'total_gastos' => $totalGastos,
            'resultado_ejercicio' => $resultado,
            'es_superavit' => $resultado > 0,
            'es_deficit' => $resultado < 0
        ];
    }

    /**
     * Verifica si un año fiscal ya fue cerrado
     */
    public function estaCerrado(string $anio): bool
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT reversado FROM cierre_ejercicio WHERE anio = ?");
        $stmt->execute([$anio]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['reversado'] === false) {
            return true;
        }

        return false;
    }

    /**
     * Ejecuta el proceso de cierre y genera el asiento contable
     */
    public function ejecutarCierre(string $anio, int $idUsuario): void
    {
        $db = Connection::getInstance();
        
        if ($this->estaCerrado($anio)) {
            throw new Exception("El ejercicio fiscal {$anio} ya se encuentra cerrado.");
        }

        $db->beginTransaction();

        try {
            // 1. Obtener todas las cuentas de ingresos (Clase 4) con saldo > 0
            // Saldo acreedor = Haber - Debe
            $stmtCuentas4 = $db->prepare("
                SELECT cc.id_cuenta_contable, cc.codigo_cuenta,
                       SUM(mc.monto_mc * CASE WHEN mc.tipo_operacion_mc = 'H' THEN 1 ELSE -1 END) as saldo
                FROM movimiento_contable mc
                JOIN comprobante_diario cd ON mc.id_comprobante_diario = cd.id_comprobante_diario
                JOIN cuenta_contable cc ON mc.id_cuenta_contable = cc.id_cuenta_contable
                WHERE cd.fecha_comprobante LIKE ? AND cc.codigo_cuenta LIKE '4.%' AND cd.eliminado = false
                GROUP BY cc.id_cuenta_contable, cc.codigo_cuenta
                HAVING SUM(mc.monto_mc * CASE WHEN mc.tipo_operacion_mc = 'H' THEN 1 ELSE -1 END) > 0
            ");
            $stmtCuentas4->execute(["{$anio}-%"]);
            $ingresos = $stmtCuentas4->fetchAll(PDO::FETCH_ASSOC);

            // 2. Obtener todas las cuentas de gastos (Clase 5) con saldo > 0
            // Saldo deudor = Debe - Haber
            $stmtCuentas5 = $db->prepare("
                SELECT cc.id_cuenta_contable, cc.codigo_cuenta,
                       SUM(mc.monto_mc * CASE WHEN mc.tipo_operacion_mc = 'D' THEN 1 ELSE -1 END) as saldo
                FROM movimiento_contable mc
                JOIN comprobante_diario cd ON mc.id_comprobante_diario = cd.id_comprobante_diario
                JOIN cuenta_contable cc ON mc.id_cuenta_contable = cc.id_cuenta_contable
                WHERE cd.fecha_comprobante LIKE ? AND cc.codigo_cuenta LIKE '5.%' AND cd.eliminado = false
                GROUP BY cc.id_cuenta_contable, cc.codigo_cuenta
                HAVING SUM(mc.monto_mc * CASE WHEN mc.tipo_operacion_mc = 'D' THEN 1 ELSE -1 END) > 0
            ");
            $stmtCuentas5->execute(["{$anio}-%"]);
            $gastos = $stmtCuentas5->fetchAll(PDO::FETCH_ASSOC);

            // Si no hay ingresos ni gastos, no hay nada que cerrar
            if (empty($ingresos) && empty($gastos)) {
                throw new Exception("No existen saldos nominales (Ingresos/Gastos) para cerrar en el año {$anio}.");
            }

            // 3. Obtener la cuenta de Resultados del Ejercicio (3.2.1.01.01)
            $stmtCuentaRes = $db->prepare("SELECT id_cuenta_contable FROM cuenta_contable WHERE codigo_cuenta = '3.2.1.01.01' LIMIT 1");
            $stmtCuentaRes->execute();
            $idCuentaResultados = $stmtCuentaRes->fetchColumn();

            if (!$idCuentaResultados) {
                throw new Exception("No se encontró la cuenta de Resultados del Ejercicio (3.2.1.01.01) en el Plan de Cuentas.");
            }

            $asiento = [];
            $totalIngresos = 0.0;
            $totalGastos = 0.0;

            // Para cerrar ingresos, los cargamos (DEBE)
            foreach ($ingresos as $ing) {
                $saldo = (float)$ing['saldo'];
                $asiento[] = [
                    'id_cuenta' => $ing['id_cuenta_contable'],
                    'tipo' => 'D',
                    'monto' => $saldo
                ];
                $totalIngresos += $saldo;
            }

            // Para cerrar gastos, los abonamos (HABER)
            foreach ($gastos as $gas) {
                $saldo = (float)$gas['saldo'];
                $asiento[] = [
                    'id_cuenta' => $gas['id_cuenta_contable'],
                    'tipo' => 'H',
                    'monto' => $saldo
                ];
                $totalGastos += $saldo;
            }

            // 4. Imputar la diferencia a Resultados del Ejercicio
            $resultado = $totalIngresos - $totalGastos;
            if (round($resultado, 2) > 0) {
                // Superávit (HABER)
                $asiento[] = [
                    'id_cuenta' => $idCuentaResultados,
                    'tipo' => 'H',
                    'monto' => round($resultado, 2)
                ];
            } elseif (round($resultado, 2) < 0) {
                // Déficit (DEBE)
                $asiento[] = [
                    'id_cuenta' => $idCuentaResultados,
                    'tipo' => 'D',
                    'monto' => round(abs($resultado), 2)
                ];
            }

            // 5. Registrar el Asiento Contable
            $fechaAsiento = "{$anio}-12-31"; // Asiento de fin de año
            $concepto = "ASIENTO DE CIERRE Y REGULARIZACIÓN DEL EJERCICIO FISCAL {$anio}";
            
            $this->asientoRepo->registrarDesdeTransaccion($fechaAsiento, $concepto, $asiento, null);

            // 6. Marcar año como cerrado
            $stmtCierre = $db->prepare("
                INSERT INTO cierre_ejercicio (anio, id_usuario, reversado)
                VALUES (?, ?, false)
                ON CONFLICT (anio) DO UPDATE SET reversado = false, id_usuario = EXCLUDED.id_usuario, fecha_cierre = CURRENT_TIMESTAMP
            ");
            $stmtCierre->execute([$anio, $idUsuario]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Revierte el proceso de cierre (SOLO PARA PRUEBAS Y CASOS EXCEPCIONALES)
     */
    public function reversarCierre(string $anio, int $idUsuario): void
    {
        $db = Connection::getInstance();
        
        if (!$this->estaCerrado($anio)) {
            throw new Exception("El ejercicio fiscal {$anio} no está cerrado.");
        }

        $db->beginTransaction();

        try {
            // 1. Eliminar (lógicamente) el comprobante de cierre
            $conceptoBusqueda = "ASIENTO DE CIERRE Y REGULARIZACIÓN DEL EJERCICIO FISCAL {$anio}";
            $stmtBuscarCd = $db->prepare("SELECT id_comprobante_diario FROM comprobante_diario WHERE concepto = ? AND fecha_comprobante LIKE ? AND eliminado = false");
            $stmtBuscarCd->execute([$conceptoBusqueda, "{$anio}-%"]);
            $idComprobante = $stmtBuscarCd->fetchColumn();

            if ($idComprobante) {
                $db->prepare("UPDATE comprobante_diario SET eliminado = true WHERE id_comprobante_diario = ?")->execute([$idComprobante]);
            }

            // 2. Marcar como reversado en cierre_ejercicio
            $db->prepare("UPDATE cierre_ejercicio SET reversado = true WHERE anio = ?")->execute([$anio]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
