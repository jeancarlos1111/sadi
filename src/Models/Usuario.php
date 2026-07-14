<?php

declare(strict_types=1);

namespace App\Models;

readonly class Usuario
{
    public function __construct(
        public int $id,
        public string $usuario,
        public ?int $cedulaPersonal
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuario' => $this->usuario,
            'cedulaPersonal' => $this->cedulaPersonal,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['usuario'] ?? null,
            $data['cedulaPersonal'] ?? null,
        );
    }
}
