<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Ficha;

class FichaDTO
{
    public function __construct(
        public int $id,
        public int $idPersonal,
        public int $idCargo,
        public int $idNomina,
        public string $fechaIngreso,
        public float $sueldoBasico,
        public int $diasUtilidades = 30,
        public int $diasBonoVacacional = 15,
        public float $porcentajeIslr = 0.0,
        public bool $eliminado = false,
        public string $tipoRelacionLaboral = 'FIJO',
        public ?string $banco = null,
        public ?string $numeroCuenta = null,
        public string $tipoCuenta = 'CORRIENTE'
    ) {
    }

    public static function fromModel(Ficha $model): self
    {
        return new self(
            $model->id,
            $model->idPersonal,
            $model->idCargo,
            $model->idNomina,
            $model->fechaIngreso,
            $model->sueldoBasico,
            $model->diasUtilidades,
            $model->diasBonoVacacional,
            $model->porcentajeIslr,
            $model->eliminado,
            $model->tipoRelacionLaboral,
            $model->banco,
            $model->numeroCuenta,
            $model->tipoCuenta
        );
    }
}
