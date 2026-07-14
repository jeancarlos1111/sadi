<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\TipoDocumento;

class TipoDocumentoDTO
{
    public function __construct(
        public string $denominacion,
        public bool $afectaPresupuesto,
        public ?string $siglas,
        public ?int $id = null
    ) {
    }

    public static function fromModel(TipoDocumento $model): self
    {
        return new self(
            $model->denominacion,
            $model->afectaPresupuesto,
            $model->siglas,
            $model->id
        );
    }
}
