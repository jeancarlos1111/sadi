<?php

namespace App\Models;

class UnidadAdministrativa
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $codigo,
        public string $denominacion,
        ?int          $id = null
    ) {
        $this->id = $id;
    }
}
