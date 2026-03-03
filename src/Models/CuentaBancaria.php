<?php

namespace App\Models;

class CuentaBancaria
{
    public function __construct(
        public int $id,
        public int $idBanco,
        public string $numeroCuenta,
        public string $nombreBanco
    ) {
    }
}
