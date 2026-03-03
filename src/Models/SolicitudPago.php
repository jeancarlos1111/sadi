<?php

namespace App\Models;

class SolicitudPago
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $fecha,
        public string $concepto,
        public float $montoPagar,
        public string $estado,
        public ?int $idDocumento,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
