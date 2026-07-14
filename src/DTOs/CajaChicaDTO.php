<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\CajaChica;

class CajaChicaDTO
{
    public function __construct(
        public ?int $id,
        public string $denominacion,
        public string $responsable,
        public float $montoAsignado,
        public float $montoDisponible,
        public string $fechaApertura,
        public bool $activa = true
    ) {
    }

    public static function fromModel(CajaChica $model): self
    {
        return new self(
            $model->id,
            $model->denominacion,
            $model->responsable,
            $model->montoAsignado,
            $model->montoDisponible,
            $model->fechaApertura,
            $model->activa
        );
    }
}
