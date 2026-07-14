<?php

declare(strict_types=1);

namespace App\Models;

readonly class MovimientoPresupuestario
{
    public ?int $id_movimiento_presupuestario;

    public function __construct(
        public int $id_comprobante,
        public int $id_estruc_presupuestaria,
        public int $id_codigo_plan_unico,
        public string $id_operacion,
        public float $monto_mp = 0.0,
        public ?string $descripcion_mp = null,
        ?int $id_movimiento_presupuestario = null
    ) {
        $this->id_movimiento_presupuestario = $id_movimiento_presupuestario;
    }

    public function toArray(): array
    {
        return [
            'id_movimiento_presupuestario' => $this->id_movimiento_presupuestario,
            'id_comprobante' => $this->id_comprobante,
            'id_estruc_presupuestaria' => $this->id_estruc_presupuestaria,
            'id_codigo_plan_unico' => $this->id_codigo_plan_unico,
            'id_operacion' => $this->id_operacion,
            'monto_mp' => $this->monto_mp,
            'descripcion_mp' => $this->descripcion_mp,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id_comprobante'] ?? null,
            $data['id_estruc_presupuestaria'] ?? null,
            $data['id_codigo_plan_unico'] ?? null,
            $data['id_operacion'] ?? null,
            $data['monto_mp'] ?? null,
            $data['descripcion_mp'] ?? null,
            $data['id_movimiento_presupuestario'] ?? null,
        );
    }
}
