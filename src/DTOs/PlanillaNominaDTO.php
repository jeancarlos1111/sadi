<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\PlanillaNomina;

class PlanillaNominaDTO
{
    public function __construct(
        public int $id,
        public int $idNomina,
        public string $fechaEmision,
        public string $periodo,
        public float $montoTotalAsignaciones = 0.0,
        public float $montoTotalDeducciones = 0.0,
        public float $montoTotalNeto = 0.0,
        public ?string $nombreNomina = null
    ) {
    }

    public static function fromModel(PlanillaNomina $model): self
    {
        return new self(
            $model->id,
            $model->idNomina,
            $model->fechaEmision,
            $model->periodo,
            $model->montoTotalAsignaciones,
            $model->montoTotalDeducciones,
            $model->montoTotalNeto,
            $model->nombreNomina
        );
    }
}
