<?php

declare(strict_types=1);

namespace App\Models;

readonly class OrdenServicio
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $fecha,
        public string $concepto,
        public int    $idProveedor,
        public float  $porcentajeIva,
        public float  $montoBase,
        public float  $montoIva,
        public float  $montoTotal,
        public bool   $contabilizada = false,
        public array  $servicios = [],
        ?int          $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha,
            'concepto' => $this->concepto,
            'idProveedor' => $this->idProveedor,
            'porcentajeIva' => $this->porcentajeIva,
            'montoBase' => $this->montoBase,
            'montoIva' => $this->montoIva,
            'montoTotal' => $this->montoTotal,
            'contabilizada' => $this->contabilizada,
            'servicios' => $this->servicios,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['fecha'] ?? null,
            $data['concepto'] ?? null,
            $data['idProveedor'] ?? null,
            $data['porcentajeIva'] ?? null,
            $data['montoBase'] ?? null,
            $data['montoIva'] ?? null,
            $data['montoTotal'] ?? null,
            $data['contabilizada'] ?? null,
            $data['servicios'] ?? null,
            $data['id'] ?? null,
        );
    }
}
