<?php

namespace App\Models;

class TipoArticulo
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string  $denominacion,
        public ?string $descripcion = null,
        public int     $tipo = 1,
        ?int           $id = null
    ) {
        $this->id = $id;
    }
}
