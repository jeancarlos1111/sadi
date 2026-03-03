<?php

namespace App\Models;

class AjustePresupuestario
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $tipoAjuste, // TRASPASO, CREDITO_ADICIONAL, REDUCCION
        public string $fecha,
        public string $concepto,
        public float $montoTotal,
        public string $estado, // PENDIENTE, APROBADO
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
