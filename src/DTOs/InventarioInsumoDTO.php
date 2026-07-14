<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\InventarioInsumo;

class InventarioInsumoDTO
{
    public function __construct(
        public ?int $id,
        public int $idArticulo,
        public float $cantidad,
        public float $minimo
    ) {
    }

    public static function fromModel(InventarioInsumo $model): self
    {
        return new self(
            $model->id,
            $model->idArticulo,
            $model->cantidad,
            $model->minimo
        );
    }
}
