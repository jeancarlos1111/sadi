<?php

declare(strict_types=1);

namespace App\Models;

readonly class SolicitudPago
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $fecha,
        public string $concepto,
        public float $montoPagar,
        public string $estado,
        public ?int $idDocumento,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha,
            'concepto' => $this->concepto,
            'montoPagar' => $this->montoPagar,
            'estado' => $this->estado,
            'idDocumento' => $this->idDocumento,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['fecha'] ?? null,
            $data['concepto'] ?? null,
            $data['montoPagar'] ?? null,
            $data['estado'] ?? null,
            $data['idDocumento'] ?? null,
            $data['id'] ?? null,
        );
    }
}
