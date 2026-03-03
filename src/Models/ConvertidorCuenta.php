<?php

namespace App\Models;

class ConvertidorCuenta
{
    public function __construct(
        public int $id_codigo_plan_unico,
        public int $id_cuenta,
        public string $tipo_operacion,
        public string $descripcion,
        public ?int $id_convertidor = null
    ) {
    }
}
