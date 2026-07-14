<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\AjustePresupuestario;

class AjustePresupuestarioDTO
{
    public function __construct(
        public ?int $id,
        public string $tipoAjuste,
        public string $fecha,
        public string $concepto,
        public float $montoTotal,
        public string $estado
    ) {
    }

    public static function fromModel(AjustePresupuestario $model): self
    {
        return new self(
            $model->id,
            $model->tipoAjuste,
            $model->fecha,
            $model->concepto,
            $model->montoTotal,
            $model->estado
        );
    }
}
