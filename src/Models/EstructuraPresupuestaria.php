<?php

declare(strict_types=1);

namespace App\Models;

readonly class EstructuraPresupuestaria
{
    public function __construct(
        public int $id,
        public string $descripcion
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'descripcion' => $this->descripcion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['descripcion'] ?? null,
        );
    }
}
