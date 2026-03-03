<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Cargo;
use PDO;

class CargoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'cargo';
    }

    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT * FROM cargo WHERE eliminado = 0 ORDER BY nombre ASC");

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new Cargo((int)$row['cod_cargo'], $row['nombre']);
        }

        return $results;
    }

    public function find(int $id): ?Cargo
    {
        $row = $this->query()->where('cod_cargo', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return new Cargo((int)$row['cod_cargo'], $row['nombre']);
    }

    public function save(Cargo $item): bool
    {
        $data = ['nombre' => $item->nombre];
        if ($item->id) {
            return $this->query()->where('cod_cargo', '=', $item->id)->update($data);
        }
        $id = $this->query()->insert($data);
        if ($id) {
            $item->id = (int)$id;

            return true;
        }

        return false;
    }
}
