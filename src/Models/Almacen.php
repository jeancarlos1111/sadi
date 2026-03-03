<?php

namespace App\Models;

class Almacen
{
    public function __construct(
        public int $id,
        public string $denominacion
    ) {
    }
}
