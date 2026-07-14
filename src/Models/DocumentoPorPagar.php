<?php

declare(strict_types=1);

namespace App\Models;

readonly class DocumentoPorPagar
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $nroDocumento,
        public string $nroControl,
        public string $fechaEmision,
        public string $fechaVencimiento,
        public int $idProveedor,
        public int $idTipoDocumento,
        public float $montoBase,
        public float $montoImpuestos,
        public float $montoTotal,
        public string $observacion,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nroDocumento' => $this->nroDocumento,
            'nroControl' => $this->nroControl,
            'fechaEmision' => $this->fechaEmision,
            'fechaVencimiento' => $this->fechaVencimiento,
            'idProveedor' => $this->idProveedor,
            'idTipoDocumento' => $this->idTipoDocumento,
            'montoBase' => $this->montoBase,
            'montoImpuestos' => $this->montoImpuestos,
            'montoTotal' => $this->montoTotal,
            'observacion' => $this->observacion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['nroDocumento'] ?? null,
            $data['nroControl'] ?? null,
            $data['fechaEmision'] ?? null,
            $data['fechaVencimiento'] ?? null,
            $data['idProveedor'] ?? null,
            $data['idTipoDocumento'] ?? null,
            $data['montoBase'] ?? null,
            $data['montoImpuestos'] ?? null,
            $data['montoTotal'] ?? null,
            $data['observacion'] ?? null,
            $data['id'] ?? null,
        );
    }
}
