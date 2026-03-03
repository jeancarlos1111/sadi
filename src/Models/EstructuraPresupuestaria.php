<?php

namespace App\Models;

class EstructuraPresupuestaria
{
    public function __construct(
        public int $id,
        public string $descripcion
    ) {
    }
}
