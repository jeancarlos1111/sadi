<?php

declare(strict_types=1);

namespace App\Models;

readonly class Rol
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $nombre,
        public ?string $descripcion = null,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['nombre']      ?? '',
            $data['descripcion'] ?? null,
            isset($data['id']) ? (int)$data['id'] : null,
        );
    }
}
