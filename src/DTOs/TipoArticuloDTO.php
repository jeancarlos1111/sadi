<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\TipoArticulo;

class TipoArticuloDTO
{
    public function __construct(
        public ?int $id,
        public string $denominacion,
        public ?string $descripcion = null,
        public int $tipo = 1
    ) {
    }

    public static function fromModel(TipoArticulo $model): self
    {
        return new self(
            $model->id,
            $model->denominacion,
            $model->descripcion,
            $model->tipo
        );
    }
}
