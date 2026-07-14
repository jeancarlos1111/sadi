<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\CuentaBancaria;

class CuentaBancariaDTO
{
    public function __construct(
        public int $id,
        public int $idBanco,
        public string $numeroCuenta,
        public string $nombreBanco
    ) {
    }

    public static function fromModel(CuentaBancaria $model): self
    {
        return new self(
            $model->id,
            $model->idBanco,
            $model->numeroCuenta,
            $model->nombreBanco
        );
    }
}
