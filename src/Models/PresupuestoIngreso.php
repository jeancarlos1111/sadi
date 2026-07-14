<?php

declare(strict_types=1);

namespace App\Models;

readonly class PresupuestoIngreso
{
    // Entidad pura para Presupuesto de Ingreso
    public function __construct(
        public int $idRamo,
        public float $montoEstimado,
        public float $montoRecaudado = 0,
        public ?int $id = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'idRamo' => $this->idRamo,
            'montoEstimado' => $this->montoEstimado,
            'montoRecaudado' => $this->montoRecaudado,
            'id' => $this->id,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idRamo'] ?? null,
            $data['montoEstimado'] ?? null,
            $data['montoRecaudado'] ?? null,
            $data['id'] ?? null,
        );
    }
}
