<?php

declare(strict_types=1);

namespace App\Models;

readonly class InventarioInsumo
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public int $idArticulo,
        public float $cantidad,
        public float $minimo,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idArticulo' => $this->idArticulo,
            'cantidad' => $this->cantidad,
            'minimo' => $this->minimo,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idArticulo'] ?? null,
            $data['cantidad'] ?? null,
            $data['minimo'] ?? null,
            $data['id'] ?? null,
        );
    }
}
