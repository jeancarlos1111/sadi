<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\UnidadMedida;

class UnidadMedidaDTO
{
    public function __construct(
        public ?int $id,
        public string $denominacion,
        public string $unidades,
        public ?string $observacion = null
    ) {
    }

    public static function fromModel(UnidadMedida $model): self
    {
        return new self(
            $model->id,
            $model->denominacion,
            $model->unidades,
            $model->observacion
        );
    }
}
