<?php

declare(strict_types=1);

namespace App\Models;

readonly class UnidadMedida
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string  $denominacion,
        public string  $unidades,
        public ?string $observacion = null,
        ?int           $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'denominacion' => $this->denominacion,
            'unidades' => $this->unidades,
            'observacion' => $this->observacion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['denominacion'] ?? null,
            $data['unidades'] ?? null,
            $data['observacion'] ?? null,
            $data['id'] ?? null,
        );
    }
}
