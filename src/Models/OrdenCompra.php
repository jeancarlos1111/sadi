<?php

namespace App\Models;

class OrdenCompra
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $fecha,
        public string $concepto,
        public int $idProveedor,
        public float $porcentajeIva,
        public float $montoBase,
        public float $montoIva,
        public float $montoTotal,
        public array $articulos = [],
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
