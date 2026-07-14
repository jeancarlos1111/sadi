<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Articulo;

class ArticuloDTO
{
    public function __construct(
        public ?int $id,
        public string $denominacion,
        public string $observacion,
        public int $idTipoDeArticulo,
        public int $idUnidadesDeMedida,
        public ?int $idCodigoPlanUnico,
        public bool $aplicarIva
    ) {
    }

    public static function fromModel(Articulo $model): self
    {
        return new self(
            $model->id,
            $model->denominacion,
            $model->observacion,
            $model->idTipoDeArticulo,
            $model->idUnidadesDeMedida,
            $model->idCodigoPlanUnico,
            $model->aplicarIva
        );
    }
}
