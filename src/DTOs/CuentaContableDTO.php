<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\CuentaContable;

class CuentaContableDTO
{
    public function __construct(
        public int $id,
        public string $codigoCuenta,
        public string $denominacion,
        public string $tipoCuenta
    ) {
    }

    public static function fromModel(CuentaContable $model): self
    {
        return new self(
            $model->id,
            $model->codigoCuenta,
            $model->denominacion,
            $model->tipoCuenta
        );
    }
}
