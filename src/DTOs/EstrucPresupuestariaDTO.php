<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\EstrucPresupuestaria;

class EstrucPresupuestariaDTO
{
    public function __construct(
        public ?int $id,
        public string $descripcion,
        public int $idAccionesCentralizadas = 0,
        public int $idAccionEspecifica = 0,
        public int $idOtrasAcciones = 0
    ) {
    }

    public static function fromModel(EstrucPresupuestaria $model): self
    {
        return new self(
            $model->id,
            $model->descripcion,
            $model->idAccionesCentralizadas,
            $model->idAccionEspecifica,
            $model->idOtrasAcciones
        );
    }
}
