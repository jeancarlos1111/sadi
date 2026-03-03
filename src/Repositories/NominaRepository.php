<?php

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
        $stmt = $db->query("SELECT * FROM nomina WHERE eliminado = 0");

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new Nomina((int)$row['cod_nomina'], $row['denom'], $row['tipo_periodo']);
        }

        return $results;
    }

    public function find(int $id): ?Nomina
    {
        $row = $this->query()->where('cod_nomina', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return new Nomina((int)$row['cod_nomina'], $row['denom'], $row['tipo_periodo']);
    }
}
