<?php

namespace App\Models;

class PresupuestoIngreso
{
    // Entidad pura para Presupuesto de Ingreso
    public function __construct(
        public int $idRamo,
        public float $montoEstimado,
        public float $montoRecaudado = 0,
        public ?int $id = null
    ) {
    }
}
