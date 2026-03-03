<?php

namespace App\Models;

class UnidadMedida
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string  $denominacion,
        public string  $unidades,
        public ?string $observacion = null,
        ?int           $id = null
    ) {
        $this->id = $id;
    }
}
