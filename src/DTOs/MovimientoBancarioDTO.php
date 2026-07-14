<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\MovimientoBancario;

class MovimientoBancarioDTO
{
    public function __construct(
        public ?int $id,
        public int $idCuenta,
        public int $idTipoOperacion,
        public float $monto,
        public string $fecha,
        public string $referencia
    ) {
    }

    public static function fromModel(MovimientoBancario $model): self
    {
        return new self(
            $model->id,
            $model->idCuenta,
            $model->idTipoOperacion,
            $model->monto,
            $model->fecha,
            $model->referencia
        );
    }
}
