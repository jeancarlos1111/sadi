<?php

namespace App\Models;

class RequisicionServicios
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $fecha,
        public string $concepto,
        public int    $idEstructura,
        public array  $servicios = [],
        ?int          $id = null
    ) {
        $this->id = $id;
    }
}
