<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\TipoServicio;

class TipoServicioDTO
{
    public function __construct(
        public ?int $id,
        public string $denominacion,
        public ?string $descripcion = null
    ) {
    }

    public static function fromModel(TipoServicio $model): self
    {
        return new self(
            $model->id,
            $model->denominacion,
            $model->descripcion
        );
    }
}
