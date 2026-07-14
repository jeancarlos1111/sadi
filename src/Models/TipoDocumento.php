<?php

declare(strict_types=1);

namespace App\Models;

readonly class TipoDocumento
{
    public function __construct(
        public string $denominacion,
        public bool $afectaPresupuesto,
        public ?string $siglas,
        public ?int $id = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'denominacion' => $this->denominacion,
            'afectaPresupuesto' => $this->afectaPresupuesto,
            'siglas' => $this->siglas,
            'id' => $this->id,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['denominacion'] ?? null,
            $data['afectaPresupuesto'] ?? null,
            $data['siglas'] ?? null,
            $data['id'] ?? null,
        );
    }
}
