<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\PresupuestoIngreso;

class PresupuestoIngresoDTO
{
    public function __construct(
        public int $idRamo,
        public float $montoEstimado,
        public float $montoRecaudado = 0,
        public ?int $id = null
    ) {
    }

    public static function fromModel(PresupuestoIngreso $model): self
    {
        return new self(
            $model->idRamo,
            $model->montoEstimado,
            $model->montoRecaudado,
            $model->id
        );
    }
}
