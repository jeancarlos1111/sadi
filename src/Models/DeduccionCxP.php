<?php

namespace App\Models;

class DeduccionCxP
{
    public function __construct(
        public string $codigo,
        public string $denominacion,
        public float $porcentaje,
        public string $aplicaSobre, // 'BASE', 'IVA', 'TOTAL'
        public bool $activo = true,
        public ?int $id = null
    ) {
    }
}
