<?php

declare(strict_types=1);

namespace App\Models;

readonly class TipoRetencion
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $denominacion,
        public float $porcentaje,
        public float $sustraendo = 0.0,
        public string $aplicaA = 'AMBAS',
        public bool $activo = true
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'denominacion' => $this->denominacion,
            'porcentaje' => $this->porcentaje,
            'sustraendo' => $this->sustraendo,
            'aplicaA' => $this->aplicaA,
            'activo' => $this->activo,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? 0,
            $data['codigo'] ?? '',
            $data['denominacion'] ?? '',
            (float)($data['porcentaje'] ?? 0),
            (float)($data['sustraendo'] ?? 0),
            $data['aplicaA'] ?? 'AMBAS',
            (bool)($data['activo'] ?? true)
        );
    }
}
