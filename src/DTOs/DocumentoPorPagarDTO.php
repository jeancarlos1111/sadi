<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\DocumentoPorPagar;

class DocumentoPorPagarDTO
{
    public function __construct(
        public ?int $id,
        public string $nroDocumento,
        public string $nroControl,
        public string $fechaEmision,
        public string $fechaVencimiento,
        public int $idProveedor,
        public int $idTipoDocumento,
        public float $montoBase,
        public float $montoImpuestos,
        public float $montoTotal,
        public string $observacion
    ) {
    }

    public static function fromModel(DocumentoPorPagar $model): self
    {
        return new self(
            $model->id,
            $model->nroDocumento,
            $model->nroControl,
            $model->fechaEmision,
            $model->fechaVencimiento,
            $model->idProveedor,
            $model->idTipoDocumento,
            $model->montoBase,
            $model->montoImpuestos,
            $model->montoTotal,
            $model->observacion
        );
    }
}
