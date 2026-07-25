<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Nomina;
use PDO;

class NominaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'nomina';
    }

    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT * FROM nomina WHERE eliminado = false ORDER BY denom ASC");

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new Nomina((int)$row['cod_nomina'], $row['denom'], $row['tipo_periodo']);
        }

        return $results;
    }

    public function countAll(): int
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT COUNT(*) FROM nomina WHERE eliminado = false");
        return (int)$stmt->fetchColumn();
    }

    public function find(int $id): ?Nomina
    {
        $row = $this->query()->where('cod_nomina', '=', $id)->where('eliminado', '=', 'false')->first();
        if (!$row) {
            return null;
        }

        return new Nomina((int)$row['cod_nomina'], $row['denom'], $row['tipo_periodo']);
    }

    public function save(Nomina $nomina): int|bool
    {
        $data = [
            'denom'       => $nomina->nombre,
            'tipo_periodo' => $nomina->tipoPeriodo,
        ];

        if ($nomina->id) {
            return $this->query()->where('cod_nomina', '=', $nomina->id)->update($data);
        }

        $id = $this->query()->insert($data);
        return $id ? (int)$id : false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('cod_nomina', '=', $id)->update(['eliminado' => 'true']);
    }
}
