<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Almacen;

class AlmacenDTO
{
    public function __construct(
        public int $id,
        public string $denominacion
    ) {
    }

    public static function fromModel(Almacen $model): self
    {
        return new self(
            $model->id,
            $model->denominacion
        );
    }
}
