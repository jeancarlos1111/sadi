<?php

namespace App\Models;

class InventarioInsumo
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public int $idArticulo,
        public float $cantidad,
        public float $minimo,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
