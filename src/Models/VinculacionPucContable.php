<?php

declare(strict_types=1);

namespace App\Models;

readonly class VinculacionPucContable
{
    public function __construct(
        public int $id_codigo_plan_unico,
        public int $id_cuenta_contable,
        public string $tipo_operacion,
        public string $descripcion,
        public ?int $id_vinculacion = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_codigo_plan_unico' => $this->id_codigo_plan_unico,
            'id_cuenta_contable' => $this->id_cuenta_contable,
            'tipo_operacion' => $this->tipo_operacion,
            'descripcion' => $this->descripcion,
            'id_vinculacion' => $this->id_vinculacion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id_codigo_plan_unico'] ?? null,
            $data['id_cuenta_contable'] ?? null,
            $data['tipo_operacion'] ?? null,
            $data['descripcion'] ?? null,
            $data['id_vinculacion'] ?? null,
        );
    }
}
