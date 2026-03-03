<?php

namespace App\Models;

class OrdenServicio
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $fecha,
        public string $concepto,
        public int    $idProveedor,
        public float  $porcentajeIva,
        public float  $montoBase,
        public float  $montoIva,
        public float  $montoTotal,
        public bool   $contabilizada = false,
        public array  $servicios = [],
        ?int          $id = null
    ) {
        $this->id = $id;
    }
}
