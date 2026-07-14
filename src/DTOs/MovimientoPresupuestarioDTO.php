<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\MovimientoPresupuestario;

class MovimientoPresupuestarioDTO
{
    public function __construct(
        public ?int $id_movimiento_presupuestario,
        public int $id_comprobante,
        public int $id_estruc_presupuestaria,
        public int $id_codigo_plan_unico,
        public string $id_operacion,
        public float $monto_mp = 0.0,
        public ?string $descripcion_mp = null
    ) {
    }

    public static function fromModel(MovimientoPresupuestario $model): self
    {
        return new self(
            $model->id_movimiento_presupuestario,
            $model->id_comprobante,
            $model->id_estruc_presupuestaria,
            $model->id_codigo_plan_unico,
            $model->id_operacion,
            $model->monto_mp,
            $model->descripcion_mp
        );
    }
}
