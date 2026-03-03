<?php

namespace App\Models;

class ComprobanteRetencion
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public int $idFactura,
        public string $tipoRetencion, // IVA, ISLR, 1X1000
        public string $numeroComprobante,
        public float $porcentaje,
        public float $montoRetenido,
        public string $fechaEmision,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
