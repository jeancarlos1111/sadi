<?php

declare(strict_types=1);

namespace App\Models;

readonly class PAC
{
    public function __construct(
        public ?int $id_proyecto,
        public ?int $id_accion_centralizada,
        public int $id_articulo,
        public float $cantidad_anual,
        public float $trim_1,
        public float $trim_2,
        public float $trim_3,
        public float $trim_4,
        public float $costo_estimado = 0,
        public string $estatus = 'PLANIFICADO',
        public ?int $id = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_proyecto' => $this->id_proyecto,
            'id_accion_centralizada' => $this->id_accion_centralizada,
            'id_articulo' => $this->id_articulo,
            'cantidad_anual' => $this->cantidad_anual,
            'trim_1' => $this->trim_1,
            'trim_2' => $this->trim_2,
            'trim_3' => $this->trim_3,
            'trim_4' => $this->trim_4,
            'costo_estimado' => $this->costo_estimado,
            'estatus' => $this->estatus,
            'id' => $this->id,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id_proyecto'] ?? null,
            $data['id_accion_centralizada'] ?? null,
            $data['id_articulo'] ?? null,
            $data['cantidad_anual'] ?? null,
            $data['trim_1'] ?? null,
            $data['trim_2'] ?? null,
            $data['trim_3'] ?? null,
            $data['trim_4'] ?? null,
            $data['costo_estimado'] ?? null,
            $data['estatus'] ?? null,
            $data['id'] ?? null,
        );
    }
}
