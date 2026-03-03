<?php

namespace App\Models;

class DocumentoPorPagar
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $nroDocumento,
        public string $nroControl,
        public string $fechaEmision,
        public string $fechaVencimiento,
        public int $idProveedor,
        public int $idTipoDocumento,
        public float $montoBase,
        public float $montoImpuestos,
        public float $montoTotal,
        public string $observacion,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
