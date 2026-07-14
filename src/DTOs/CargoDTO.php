<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Cargo;

class CargoDTO
{
    public function __construct(
        public int $id,
        public string $nombre
    ) {
    }

    public static function fromModel(Cargo $model): self
    {
        return new self(
            $model->id,
            $model->nombre
        );
    }
}
