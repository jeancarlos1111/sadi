<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\TipoOperacionBancaria;

class TipoOperacionBancariaDTO
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $acronimo
    ) {
    }

    public static function fromModel(TipoOperacionBancaria $model): self
    {
        return new self(
            $model->id,
            $model->nombre,
            $model->acronimo
        );
    }
}
