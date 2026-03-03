<?php

namespace App\Models;

class MovimientoPresupuestario
{
    public ?int $id_movimiento_presupuestario;

    public function __construct(
        public int $id_comprobante,
        public int $id_estruc_presupuestaria,
        public int $id_codigo_plan_unico,
        public string $id_operacion,
        public float $monto_mp = 0.0,
        public ?string $descripcion_mp = null,
        ?int $id_movimiento_presupuestario = null
    ) {
        $this->id_movimiento_presupuestario = $id_movimiento_presupuestario;
    }
}
