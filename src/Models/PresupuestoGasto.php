<?php

namespace App\Models;

class PresupuestoGasto
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public int $idEstructura,
        public int $idPlanUnico,
        public float $montoAsignado,
        public float $montoComprometido = 0,
        public float $montoCausado = 0,
        public float $montoPagado = 0,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
