<?php

namespace App\Models;

class CuentaContable
{
    public function __construct(
        public int $id,
        public string $codigoCuenta,
        public string $denominacion,
        public string $tipoCuenta
    ) {
    }
}
