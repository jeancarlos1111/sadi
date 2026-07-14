<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Nomina;

class NominaDTO
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $tipoPeriodo
    ) {
    }

    public static function fromModel(Nomina $model): self
    {
        return new self(
            $model->id,
            $model->nombre,
            $model->tipoPeriodo
        );
    }
}
