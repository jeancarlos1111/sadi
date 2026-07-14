<?php

declare(strict_types=1);

namespace App\Models;

readonly class FuenteFinanciamiento
{
    public function __construct(
        public string $denominacion,
        public ?int $id = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'denominacion' => $this->denominacion,
            'id' => $this->id,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['denominacion'] ?? null,
            $data['id'] ?? null,
        );
    }
}
