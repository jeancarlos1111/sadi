<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\EstructuraPresupuestaria;

class EstructuraPresupuestariaDTO
{
    public function __construct(
        public int $id,
        public string $descripcion
    ) {
    }

    public static function fromModel(EstructuraPresupuestaria $model): self
    {
        return new self(
            $model->id,
            $model->descripcion
        );
    }
}
