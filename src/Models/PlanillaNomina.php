<?php

declare(strict_types=1);

namespace App\Models;

readonly class PlanillaNomina
{
    public function __construct(
        public int $id,
        public int $idNomina,
        public string $fechaEmision,
        public string $periodo,
        public float $montoTotalAsignaciones = 0.0,
        public float $montoTotalDeducciones = 0.0,
        public float $montoTotalNeto = 0.0,
        public ?string $nombreNomina = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idNomina' => $this->idNomina,
            'fechaEmision' => $this->fechaEmision,
            'periodo' => $this->periodo,
            'montoTotalAsignaciones' => $this->montoTotalAsignaciones,
            'montoTotalDeducciones' => $this->montoTotalDeducciones,
            'montoTotalNeto' => $this->montoTotalNeto,
            'nombreNomina' => $this->nombreNomina,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['idNomina'] ?? null,
            $data['fechaEmision'] ?? null,
            $data['periodo'] ?? null,
            $data['montoTotalAsignaciones'] ?? null,
            $data['montoTotalDeducciones'] ?? null,
            $data['montoTotalNeto'] ?? null,
            $data['nombreNomina'] ?? null,
        );
    }
}
