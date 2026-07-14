<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Servicio;

class ServicioDTO
{
    public function __construct(
        public ?int $id,
        public string $denominacion,
        public ?string $descripcion,
        public int $idTipoServicio,
        public bool $aplicarIva = false,
        public ?int $idCodigoPlanUnico = null
    ) {
    }

    public static function fromModel(Servicio $model): self
    {
        return new self(
            $model->id,
            $model->denominacion,
            $model->descripcion,
            $model->idTipoServicio,
            $model->aplicarIva,
            $model->idCodigoPlanUnico
        );
    }
}
