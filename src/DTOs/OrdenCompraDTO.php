<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\OrdenCompra;

class OrdenCompraDTO
{
    public function __construct(
        public ?int $id,
        public string $fecha,
        public string $concepto,
        public int $idProveedor,
        public float $porcentajeIva,
        public float $montoBase,
        public float $montoIva,
        public float $montoTotal,
        public array $articulos = []
    ) {
    }

    public static function fromModel(OrdenCompra $model): self
    {
        return new self(
            $model->id,
            $model->fecha,
            $model->concepto,
            $model->idProveedor,
            $model->porcentajeIva,
            $model->montoBase,
            $model->montoIva,
            $model->montoTotal,
            $model->articulos
        );
    }
}
