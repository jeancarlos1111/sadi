<?php

declare(strict_types=1);

namespace App\Models;

readonly class Nomina
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $tipoPeriodo
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipoPeriodo' => $this->tipoPeriodo,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['nombre'] ?? null,
            $data['tipoPeriodo'] ?? null,
        );
    }
}
