<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\RequisicionBienes;

class RequisicionBienesDTO
{
    public function __construct(
        public ?int $id,
        public string $fecha,
        public string $concepto,
        public int $idEstructuraPresupuestaria,
        public array $articulos = []
    ) {
    }

    public static function fromModel(RequisicionBienes $model): self
    {
        return new self(
            $model->id,
            $model->fecha,
            $model->concepto,
            $model->idEstructuraPresupuestaria,
            $model->articulos
        );
    }
}
