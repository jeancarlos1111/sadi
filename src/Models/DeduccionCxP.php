<?php

declare(strict_types=1);

namespace App\Models;

readonly class DeduccionCxP
{
    public function __construct(
        public string $codigo,
        public string $denominacion,
        public float $porcentaje,
        public string $aplicaSobre, // 'BASE', 'IVA', 'TOTAL'
        public bool $activo = true,
        public ?int $id = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'denominacion' => $this->denominacion,
            'porcentaje' => $this->porcentaje,
            'aplicaSobre' => $this->aplicaSobre,
            'activo' => $this->activo,
            'id' => $this->id,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['codigo'] ?? null,
            $data['denominacion'] ?? null,
            $data['porcentaje'] ?? null,
            $data['aplicaSobre'] ?? null,
            $data['activo'] ?? null,
            $data['id'] ?? null,
        );
    }
}
