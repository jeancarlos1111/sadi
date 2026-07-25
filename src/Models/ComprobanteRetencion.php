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
        public ?int $idTipoRetencion = null,
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
            'idTipoRetencion' => $this->idTipoRetencion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['idFactura'] ?? 0),
            $data['tipoRetencion'] ?? '',
            $data['numeroComprobante'] ?? '',
            (float)($data['porcentaje'] ?? 0),
            (float)($data['montoRetenido'] ?? 0),
            $data['fechaEmision'] ?? '',
            $data['idTipoRetencion'] ?? null,
            $data['id'] ?? null,
        );
    }
}
