<?php

namespace App\Models;

class TipoDocumento
{
    public function __construct(
        public string $denominacion,
        public bool $afectaPresupuesto,
        public ?string $siglas,
        public ?int $id = null
    ) {
    }
}
