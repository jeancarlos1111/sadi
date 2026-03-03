<?php

namespace App\Models;

class MovimientoBancario
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public int $idCuenta,
        public int $idTipoOperacion,
        public float $monto,
        public string $fecha,
        public string $referencia,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
