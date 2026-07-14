<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\ComprobanteRetencion;

class ComprobanteRetencionDTO
{
    public function __construct(
        public ?int $id,
        public int $idFactura,
        public string $tipoRetencion,
        public string $numeroComprobante,
        public float $porcentaje,
        public float $montoRetenido,
        public string $fechaEmision
    ) {
    }

    public static function fromModel(ComprobanteRetencion $model): self
    {
        return new self(
            $model->id,
            $model->idFactura,
            $model->tipoRetencion,
            $model->numeroComprobante,
            $model->porcentaje,
            $model->montoRetenido,
            $model->fechaEmision
        );
    }
}
