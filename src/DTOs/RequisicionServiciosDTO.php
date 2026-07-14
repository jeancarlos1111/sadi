<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\RequisicionServicios;

class RequisicionServiciosDTO
{
    public function __construct(
        public ?int $id,
        public string $fecha,
        public string $concepto,
        public int $idEstructura,
        public array $servicios = []
    ) {
    }

    public static function fromModel(RequisicionServicios $model): self
    {
        return new self(
            $model->id,
            $model->fecha,
            $model->concepto,
            $model->idEstructura,
            $model->servicios
        );
    }
}
