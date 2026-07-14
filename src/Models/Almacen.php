<?php

declare(strict_types=1);

namespace App\Models;

readonly class Almacen
{
    public function __construct(
        public int $id,
        public string $denominacion
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'denominacion' => $this->denominacion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['denominacion'] ?? null,
        );
    }
}
