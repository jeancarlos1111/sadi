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
        public bool $eliminado = false
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
            $model->eliminado
        );
    }
}
