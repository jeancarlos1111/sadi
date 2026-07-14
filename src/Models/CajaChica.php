<?php

declare(strict_types=1);

namespace App\Models;

readonly class CajaChica
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $denominacion,
        public string $responsable,
        public float $montoAsignado,
        public float $montoDisponible,
        public string $fechaApertura,
        public bool $activa = true,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'denominacion' => $this->denominacion,
            'responsable' => $this->responsable,
            'montoAsignado' => $this->montoAsignado,
            'montoDisponible' => $this->montoDisponible,
            'fechaApertura' => $this->fechaApertura,
            'activa' => $this->activa,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['denominacion'] ?? null,
            $data['responsable'] ?? null,
            $data['montoAsignado'] ?? null,
            $data['montoDisponible'] ?? null,
            $data['fechaApertura'] ?? null,
            $data['activa'] ?? null,
            $data['id'] ?? null,
        );
    }
}
