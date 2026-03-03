<?php

namespace App\Repositories;

use App\Database\Repository;
use PDO;

class ReformulacionRepository extends Repository
{
    protected function getTable(): string
    {
        return 'reformulacion';
    }

    /**
     * Devuelve la comparación entre Formulación original (presupuesto_gastos)
     * y Reformulación (reformulacion) con la diferencia calculada para cada partida.
     * Fiel al Form_AJUSTAR_PRESUPUESTO_REFORMULACION__Cargar() de SIGAFS.
     */
    public function getComparativa(?int $id_estruc = null): array
    {
        $where = '';
        $params = [];
        if ($id_estruc !== null) {
            $where = ' AND COALESCE(f.id_estruc_presupuestaria, r.id_estruc_presupuestaria) = :id_ep';
            $params['id_ep'] = $id_estruc;
        }

        $sql = "
            SELECT
                COALESCE(f.id_estruc_presupuestaria, r.id_estruc_presupuestaria) AS id_estruc_presupuestaria,
                ep.descripcion_ep,
                COALESCE(f.id_codigo_plan_unico, r.id_codigo_plan_unico) AS id_codigo_plan_unico,
                puc.codigo_plan_unico,
                puc.denominacion AS denominacion_cuenta,
                COALESCE(f.monto_asignado, 0) AS total_formulado,
                COALESCE(r.monto_reformulado, 0) AS total_reformulado,
                COALESCE(r.monto_reformulado, 0) - COALESCE(f.monto_asignado, 0) AS diferencia
            FROM presupuesto_gastos f
            LEFT JOIN reformulacion r
                ON f.id_estruc_presupuestaria = r.id_estruc_presupuestaria
               AND f.id_codigo_plan_unico = r.id_codigo_plan_unico
               AND (r.eliminado = 0 OR r.eliminado IS NULL)
            JOIN estruc_presupuestaria ep  ON ep.id_estruc_presupuestaria = COALESCE(f.id_estruc_presupuestaria, r.id_estruc_presupuestaria)
            JOIN plan_unico_cuentas puc    ON puc.id_codigo_plan_unico = COALESCE(f.id_codigo_plan_unico, r.id_codigo_plan_unico)
            WHERE (f.eliminado = 0 OR f.eliminado IS NULL)
            $where
            ORDER BY ep.descripcion_ep ASC, puc.codigo_plan_unico ASC
        ";

        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda (UPSERT) el monto reformulado para una estructura/cuenta.
     */
    public function upsert(int $id_estruc, int $id_cuenta, float $monto, ?string $observacion = null): void
    {
        $stmt = $this->getPdo()->prepare("
            INSERT INTO {$this->getTable()} (id_estruc_presupuestaria, id_codigo_plan_unico, monto_reformulado, observacion)
            VALUES (:id_ep, :id_cpu, :monto, :obs)
            ON CONFLICT(id_estruc_presupuestaria, id_codigo_plan_unico) DO UPDATE SET
                monto_reformulado = excluded.monto_reformulado,
                observacion       = excluded.observacion,
                fecha_registro    = date('now'),
                eliminado         = 0
        ");
        $stmt->execute([
            'id_ep'  => $id_estruc,
            'id_cpu' => $id_cuenta,
            'monto'  => $monto,
            'obs'    => $observacion,
        ]);
    }
}
