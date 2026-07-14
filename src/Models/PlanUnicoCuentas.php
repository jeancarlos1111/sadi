<?php

declare(strict_types=1);

namespace App\Models;

readonly class PlanUnicoCuentas
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $codigo,
        public string $denominacion,
        ?int          $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'denominacion' => $this->denominacion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['codigo'] ?? null,
            $data['denominacion'] ?? null,
            $data['id'] ?? null,
        );
    }
}
