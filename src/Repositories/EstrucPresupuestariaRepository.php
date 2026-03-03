<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\EstructuraPresupuestaria;
use PDO;

class EstrucPresupuestariaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'estruc_presupuestaria';
    }

    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT id_estruc_presupuestaria, descripcion_ep FROM estruc_presupuestaria WHERE eliminado = 0 ORDER BY descripcion_ep");

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new EstructuraPresupuestaria(
                (int)$row['id_estruc_presupuestaria'],
                $row['descripcion_ep']
            );
        }

        return $results;
    }

    public function find(int $id): ?EstructuraPresupuestaria
    {
        $row = $this->query()->where('id_estruc_presupuestaria', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return new EstructuraPresupuestaria(
            (int)$row['id_estruc_presupuestaria'],
            $row['descripcion_ep']
        );
    }

    public function save(EstructuraPresupuestaria $item): bool
    {
        $data = [
            'descripcion_ep' => $item->descripcion,
        ];

        if ($item->id) {
            return $this->query()->where('id_estruc_presupuestaria', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        if ($id) {
            // El modelo tiene $id como public en el constructor, pero no podemos cambiarlo si es readonly o similar
            // En este caso es public.
            $item->id = (int)$id;

            return true;
        }

        return false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_estruc_presupuestaria', '=', $id)->update(['eliminado' => 1]);
    }
}
