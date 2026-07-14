<?php

declare(strict_types=1);

namespace App\Models;

readonly class Banco
{
    public function __construct(
        public int $id,
        public string $nombreBanco
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombreBanco' => $this->nombreBanco,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['nombreBanco'] ?? null,
        );
    }
}
