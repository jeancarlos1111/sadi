<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\SolicitudPago;

class SolicitudPagoDTO
{
    public function __construct(
        public ?int $id,
        public string $fecha,
        public string $concepto,
        public float $montoPagar,
        public string $estado,
        public ?int $idDocumento
    ) {
    }

    public static function fromModel(SolicitudPago $model): self
    {
        return new self(
            $model->id,
            $model->fecha,
            $model->concepto,
            $model->montoPagar,
            $model->estado,
            $model->idDocumento
        );
    }
}
