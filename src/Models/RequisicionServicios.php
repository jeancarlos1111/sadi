<?php

declare(strict_types=1);

namespace App\Models;

readonly class RequisicionServicios
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $fecha,
        public string $concepto,
        public int    $idEstructura,
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
            'idEstructura' => $this->idEstructura,
            'servicios' => $this->servicios,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['fecha'] ?? null,
            $data['concepto'] ?? null,
            $data['idEstructura'] ?? null,
            $data['servicios'] ?? null,
            $data['id'] ?? null,
        );
    }
}
