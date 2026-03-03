<?php

namespace App\Models;

class PlanillaNomina
{
    public function __construct(
        public int $id,
        public int $idNomina,
        public string $fechaEmision,
        public string $periodo,
        public float $montoTotalAsignaciones = 0.0,
        public float $montoTotalDeducciones = 0.0,
        public float $montoTotalNeto = 0.0,
        public ?string $nombreNomina = null
    ) {
    }
}
