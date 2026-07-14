<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\VinculacionPucContable;

class VinculacionPucContableDTO
{
    public function __construct(
        public int $id_codigo_plan_unico,
        public int $id_cuenta_contable,
        public string $tipo_operacion,
        public string $descripcion,
        public ?int $id_vinculacion = null
    ) {
    }

    public static function fromModel(VinculacionPucContable $model): self
    {
        return new self(
            $model->id_codigo_plan_unico,
            $model->id_cuenta_contable,
            $model->tipo_operacion,
            $model->descripcion,
            $model->id_vinculacion
        );
    }
}
