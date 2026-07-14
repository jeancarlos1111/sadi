<?php

declare(strict_types=1);

namespace App\Models;

readonly class CuentaBancaria
{
    public function __construct(
        public int $id,
        public int $idBanco,
        public string $numeroCuenta,
        public string $nombreBanco
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idBanco' => $this->idBanco,
            'numeroCuenta' => $this->numeroCuenta,
            'nombreBanco' => $this->nombreBanco,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['idBanco'] ?? null,
            $data['numeroCuenta'] ?? null,
            $data['nombreBanco'] ?? null,
        );
    }
}
