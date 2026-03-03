<?php

namespace App\Models;

class TipoServicio
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string  $denominacion,
        public ?string $descripcion = null,
        ?int           $id = null
    ) {
        $this->id = $id;
    }
}
