<?php

declare(strict_types=1);

namespace App\Models;

readonly class ComprobanteRetencion
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public int $idFactura,
        public string $tipoRetencion, // IVA, ISLR, 1X1000
        public string $numeroComprobante,
        public float $porcentaje,
        public float $montoRetenido,
        public string $fechaEmision,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idFactura' => $this->idFactura,
            'tipoRetencion' => $this->tipoRetencion,
            'numeroComprobante' => $this->numeroComprobante,
            'porcentaje' => $this->porcentaje,
            'montoRetenido' => $this->montoRetenido,
            'fechaEmision' => $this->fechaEmision,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idFactura'] ?? null,
            $data['tipoRetencion'] ?? null,
            $data['numeroComprobante'] ?? null,
            $data['porcentaje'] ?? null,
            $data['montoRetenido'] ?? null,
            $data['fechaEmision'] ?? null,
            $data['id'] ?? null,
        );
    }
}
