<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\UnidadAdministrativa;

class UnidadAdministrativaDTO
{
    public function __construct(
        public ?int $id,
        public string $codigo,
        public string $denominacion
    ) {
    }

    public static function fromModel(UnidadAdministrativa $model): self
    {
        return new self(
            $model->id,
            $model->codigo,
            $model->denominacion
        );
    }
}
