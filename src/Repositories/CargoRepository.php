<?php

declare(strict_types=1);

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

    public function countAll(): int
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT COUNT(*) FROM cargo WHERE eliminado = false");
        return (int)$stmt->fetchColumn();
    }

    public function all(?int $limit = null, ?int $offset = null): array
    {
        $db = $this->getPdo();
        $sql = "SELECT * FROM cargo WHERE eliminado = false ORDER BY nombre ASC";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        if ($offset !== null) {
            $sql .= " OFFSET " . (int)$offset;
        }

        $stmt = $db->query($sql);

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = clone (new Cargo((int)$row['cod_cargo'], $row['nombre']));
        }

        return $results;
    }

    public function find(int $id): ?Cargo
    {
        $row = $this->query()->where('cod_cargo', '=', $id)->where('eliminado', '=', 'false')->first();
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
