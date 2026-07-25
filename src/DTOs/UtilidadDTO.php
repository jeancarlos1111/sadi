<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Utilidad;

class UtilidadDTO
{
    public function __construct(
        public int $anio,
        public int $diasBase,
        public float $montoTotalNomina,
        public string $estatus,
        public bool $eliminado,
        public ?int $id = null
    ) {}

    public static function fromModel(Utilidad $model): self
    {
        return new self(
            $model->anio,
            $model->diasBase,
            $model->montoTotalNomina,
            $model->estatus,
            $model->eliminado,
            $model->id
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['anio'] ?? date('Y')),
            (int)($data['dias_base'] ?? 30),
            (float)($data['monto_total_nomina'] ?? 0.0),
            $data['estatus'] ?? 'Generado',
            isset($data['eliminado']) ? (bool)$data['eliminado'] : false,
            isset($data['id_utilidad']) ? (int)$data['id_utilidad'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id_utilidad' => $this->id,
            'anio' => $this->anio,
            'dias_base' => $this->diasBase,
            'monto_total_nomina' => $this->montoTotalNomina,
            'estatus' => $this->estatus,
            'eliminado' => $this->eliminado
        ];
    }
}
