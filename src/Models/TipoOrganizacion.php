<?php

declare(strict_types=1);

namespace App\Models;

readonly class TipoOrganizacion
{
    public function __construct(
        public int $id,
        public string $nombre
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['nombre'] ?? null,
        );
    }
}
