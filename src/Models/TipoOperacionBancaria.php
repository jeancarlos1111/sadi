<?php

declare(strict_types=1);

namespace App\Models;

readonly class TipoOperacionBancaria
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $acronimo
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'acronimo' => $this->acronimo,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['nombre'] ?? null,
            $data['acronimo'] ?? null,
        );
    }
}
