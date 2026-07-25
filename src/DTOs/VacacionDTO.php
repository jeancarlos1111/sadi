<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Vacacion;

class VacacionDTO
{
    public function __construct(
        public int $id,
        public int $codFicha,
        public string $fechaSalida,
        public string $fechaRetorno,
        public int $diasDisfrute,
        public int $diasBono,
        public float $montoVacaciones,
        public float $montoBono,
        public float $montoTotal,
        public string $estatus,
        public bool $eliminado,
        public ?string $nombreTrabajador = null
    ) {
    }

    public static function fromModel(Vacacion $model): self
    {
        return new self(
            $model->id ?? 0,
            $model->codFicha,
            $model->fechaSalida,
            $model->fechaRetorno,
            $model->diasDisfrute,
            $model->diasBono,
            $model->montoVacaciones,
            $model->montoBono,
            $model->montoTotal,
            $model->estatus,
            $model->eliminado
        );
    }
}
