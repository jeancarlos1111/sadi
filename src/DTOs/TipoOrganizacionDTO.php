<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\TipoOrganizacion;

class TipoOrganizacionDTO
{
    public function __construct(
        public int $id,
        public string $nombre
    ) {
    }

    public static function fromModel(TipoOrganizacion $model): self
    {
        return new self(
            $model->id,
            $model->nombre
        );
    }
}
