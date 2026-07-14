<?php

declare(strict_types=1);

namespace App\Models;

readonly class TipoOperacionPresupuesto
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string  $denominacion,
        public ?string $descripcion = null,
        ?int           $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'denominacion' => $this->denominacion,
            'descripcion' => $this->descripcion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['denominacion'] ?? null,
            $data['descripcion'] ?? null,
            $data['id'] ?? null,
        );
    }
}
