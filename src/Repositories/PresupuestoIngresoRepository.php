<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\AsientoContable;
use Exception;
use PDO;

class PresupuestoIngresoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'presupuesto_ingresos';
    }

    public function allRamos(): array
    {
        $db = $this->getPdo();

        return $db->query("SELECT * FROM presupuesto_ingresos_ramo WHERE eliminado = 0 ORDER BY codigo_ramo")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allFormulado(): array
    {
        $db = $this->getPdo();

        return $db->query("
            SELECT pi.*, pir.codigo_ramo, pir.denominacion_ramo 
            FROM presupuesto_ingresos pi
            JOIN presupuesto_ingresos_ramo pir ON pi.id_ramo = pir.id_ramo
            WHERE pi.eliminado = 0
            ORDER BY pir.codigo_ramo
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function formular(int $id_ramo, float $monto_estimado_inicial): bool
    {
        $db = $this->getPdo();

        // Verifica si ya existe, si sí suma, si no inserta
        $stmt = $db->prepare("SELECT id_presupuesto_ingreso FROM presupuesto_ingresos WHERE id_ramo = ? AND eliminado = 0");
        $stmt->execute([$id_ramo]);
        $existe = $stmt->fetchColumn();

        if ($existe) {
            $upd = $db->prepare("UPDATE presupuesto_ingresos SET monto_estimado_inicial = monto_estimado_inicial + ? WHERE id_presupuesto_ingreso = ?");

            return $upd->execute([$monto_estimado_inicial, $existe]);
        } else {
            $ins = $db->prepare("INSERT INTO presupuesto_ingresos (id_ramo, monto_estimado_inicial) VALUES (?, ?)");

            return $ins->execute([$id_ramo, $monto_estimado_inicial]);
        }
    }

    public function recaudar(int $id_presupuesto_ingreso, float $monto_recaudado, string $referencia = ''): bool
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            // Obtener el registro para saber el id_ramo
            $stmt = $db->prepare("SELECT id_ramo FROM presupuesto_ingresos WHERE id_presupuesto_ingreso = ?");
            $stmt->execute([$id_presupuesto_ingreso]);
            $id_ramo = $stmt->fetchColumn();

            if (!$id_ramo) {
                throw new Exception("Presupuesto de ingreso no encontrado.");
            }

            // Actualizar monto recaudado
            $upd = $db->prepare("UPDATE presupuesto_ingresos SET monto_recaudado = monto_recaudado + ? WHERE id_presupuesto_ingreso = ?");
            $upd->execute([$monto_recaudado, $id_presupuesto_ingreso]);

            // Asiento Contable Automático (Cruza con Cuenta Bancaria al Debe, e Ingreso al Haber)
            // Se asume configurado en el Convertidor o se usan mocks (Banco = 2 (Debe), Ingreso = 5 (Haber))
            $asientoDetalles = [];

            // Mockeamos la regla para el MVP si no está en convertidor
            $asientoDetalles[] = ['id_cuenta' => 2, 'tipo' => 'D', 'monto' => $monto_recaudado]; // Bancos sube
            $asientoDetalles[] = ['id_cuenta' => 5, 'tipo' => 'H', 'monto' => $monto_recaudado]; // Cuenta Ingresos Patrimoniales sube

            AsientoContable::registrarDesdeTransaccion(
                date('Y-m-d'),
                "Liquidación y Recaudación de Ingreso - Ref: {$referencia}",
                $asientoDetalles
            );

            $db->commit();

            return true;
        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }
}
