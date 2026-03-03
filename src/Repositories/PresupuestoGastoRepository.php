<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\PresupuestoGasto;
use PDO;

class PresupuestoGastoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'presupuesto_gastos';
    }

    /**
     * @return array
     */
    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                PG.id_presupuesto_gastos, PG.id_estruc_presupuestaria, PG.id_codigo_plan_unico,
                PG.monto_asignado, PG.monto_comprometido, PG.monto_causado, PG.monto_pagado,
                EP.descripcion_ep,
                PU.codigo_plan_unico, PU.denominacion AS partida_denominacion
            FROM presupuesto_gastos AS PG
            JOIN estruc_presupuestaria AS EP ON PG.id_estruc_presupuestaria = EP.id_estruc_presupuestaria
            JOIN plan_unico_cuentas AS PU ON PG.id_codigo_plan_unico = PU.id_codigo_plan_unico
            WHERE PG.eliminado = 0
        ";

        if ($search !== '') {
            $sql .= " AND (
                EP.descripcion_ep LIKE :search
                OR PU.codigo_plan_unico LIKE :search
                OR PU.denominacion LIKE :search
            )";
        }
        $sql .= " ORDER BY EP.descripcion_ep ASC, PU.codigo_plan_unico ASC";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $pg = new PresupuestoGasto(
                (int)$row['id_estruc_presupuestaria'],
                (int)$row['id_codigo_plan_unico'],
                (float)($row['monto_asignado'] ?? 0),
                (float)($row['monto_comprometido'] ?? 0),
                (float)($row['monto_causado'] ?? 0),
                (float)($row['monto_pagado'] ?? 0),
                (int)$row['id_presupuesto_gastos']
            );
            $results[] = [
                'entity'         => $pg,
                'estructura_desc' => $row['descripcion_ep'],
                'partida_codigo' => $row['codigo_plan_unico'],
                'partida_desc'   => $row['partida_denominacion'],
                'disponibilidad' => $pg->montoAsignado - $pg->montoComprometido,
            ];
        }

        return $results;
    }

    public function find(int $id): ?PresupuestoGasto
    {
        $row = $this->query()->where('id_presupuesto_gastos', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return new PresupuestoGasto(
            (int)$row['id_estruc_presupuestaria'],
            (int)$row['id_codigo_plan_unico'],
            (float)$row['monto_asignado'],
            (float)$row['monto_comprometido'],
            (float)$row['monto_causado'],
            (float)$row['monto_pagado'],
            (int)$row['id_presupuesto_gastos']
        );
    }

    public function save(PresupuestoGasto $item): bool
    {
        $data = [
            'id_estruc_presupuestaria' => $item->idEstructura,
            'id_codigo_plan_unico'    => $item->idPlanUnico,
            'monto_asignado'          => $item->montoAsignado,
        ];

        if ($item->id) {
            return $this->query()->where('id_presupuesto_gastos', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        if ($id) {
            $item->/* private(set) */ id = (int)$id;

            return true;
        }

        return false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_presupuesto_gastos', '=', $id)->update(['eliminado' => 1]);
    }

    public function getEstructuras(): array
    {
        return $this->getPdo()->query("SELECT id_estruc_presupuestaria, descripcion_ep FROM estruc_presupuestaria WHERE eliminado = 0 ORDER BY descripcion_ep")
                    ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPartidas(): array
    {
        return $this->getPdo()->query("SELECT id_codigo_plan_unico, codigo_plan_unico, denominacion FROM plan_unico_cuentas WHERE eliminado = 0 ORDER BY codigo_plan_unico")
                    ->fetchAll(PDO::FETCH_ASSOC);
    }
}
