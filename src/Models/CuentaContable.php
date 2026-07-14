<?php

declare(strict_types=1);

namespace App\Models;

readonly class CuentaContable
{
    public function __construct(
        public int $id,
        public string $codigoCuenta,
        public string $denominacion,
        public string $tipoCuenta
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigoCuenta' => $this->codigoCuenta,
            'denominacion' => $this->denominacion,
            'tipoCuenta' => $this->tipoCuenta,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['codigoCuenta'] ?? null,
            $data['denominacion'] ?? null,
            $data['tipoCuenta'] ?? null,
        );
    }
}
