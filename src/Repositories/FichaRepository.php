<?php

declare(strict_types=1);

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
        $row = $this->query()->where('cod_ficha', '=', $id)->where('eliminado', '=', 'false')->first();
        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    /**
     * @return Ficha[]
     */
    public function allActivos(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT * FROM ficha WHERE eliminado = false ORDER BY cod_ficha DESC");
        return array_map(fn ($row) => clone $this->mapRowToEntity($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function save(Ficha $item): int|bool
    {
        $data = [
            'personal_cod_personal' => $item->idPersonal,
            'cargo_cod_cargo' => $item->idCargo,
            'nomina_cod_nomina' => $item->idNomina,
            'ingreso' => $item->fechaIngreso,
            'sueldo_basico' => $item->sueldoBasico,
            'dias_utilidades' => $item->diasUtilidades,
            'dias_bono_vacacional' => $item->diasBonoVacacional,
            'porcentaje_islr' => $item->porcentajeIslr,
            'tipo_relacion_laboral' => $item->tipoRelacionLaboral,
            'banco' => $item->banco,
            'numero_cuenta' => $item->numeroCuenta,
            'tipo_cuenta' => $item->tipoCuenta,
        ];

        if ($item->id) {
            return $this->query()->where('cod_ficha', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        return (int)$id;
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
            (int)($row['dias_utilidades'] ?? 30),
            (int)($row['dias_bono_vacacional'] ?? 15),
            (float)($row['porcentaje_islr'] ?? 0.0),
            (bool)$row['eliminado'],
            $row['tipo_relacion_laboral'] ?? 'FIJO',
            $row['banco'] ?? null,
            $row['numero_cuenta'] ?? null,
            $row['tipo_cuenta'] ?? 'CORRIENTE'
        );
    }
}
