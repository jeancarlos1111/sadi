<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\DeduccionCxP;

class DeduccionCxPDTO
{
    public function __construct(
        public string $codigo,
        public string $denominacion,
        public float $porcentaje,
        public string $aplicaSobre,
        public bool $activo = true,
        public ?int $id = null
    ) {
    }

    public static function fromModel(DeduccionCxP $model): self
    {
        return new self(
            $model->codigo,
            $model->denominacion,
            $model->porcentaje,
            $model->aplicaSobre,
            $model->activo,
            $model->id
        );
    }
}
