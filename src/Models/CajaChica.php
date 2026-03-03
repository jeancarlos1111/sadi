<?php

namespace App\Models;

class CajaChica
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $denominacion,
        public string $responsable,
        public float $montoAsignado,
        public float $montoDisponible,
        public string $fechaApertura,
        public bool $activa = true,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
