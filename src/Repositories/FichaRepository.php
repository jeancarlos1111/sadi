<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Ficha;

class FichaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'ficha';
    }

    public function find(int $id): ?Ficha
    {
        $row = $this->query()->where('cod_ficha', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function save(Ficha $item): bool
    {
        $data = [
            'personal_cod_personal' => $item->idPersonal,
            'cargo_cod_cargo' => $item->idCargo,
            'nomina_cod_nomina' => $item->idNomina,
            'ingreso' => $item->fechaIngreso,
            'sueldo_basico' => $item->sueldoBasico,
        ];

        if ($item->id) {
            return $this->query()->where('cod_ficha', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        if ($id) {
            $item->id = (int)$id;

            return true;
        }

        return false;
    }

    private function mapRowToEntity(array $row): Ficha
    {
        return new Ficha(
            (int)$row['cod_ficha'],
            (int)$row['personal_cod_personal'],
            (int)$row['cargo_cod_cargo'],
            (int)$row['nomina_cod_nomina'],
            $row['ingreso'],
            (float)$row['sueldo_basico'],
            (bool)$row['eliminado']
        );
    }
}
