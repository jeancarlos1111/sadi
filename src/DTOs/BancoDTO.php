<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Banco;

class BancoDTO
{
    public function __construct(
        public int $id,
        public string $nombreBanco
    ) {
    }

    public static function fromModel(Banco $model): self
    {
        return new self(
            $model->id,
            $model->nombreBanco
        );
    }
}
