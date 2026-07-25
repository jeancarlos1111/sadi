<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Proyecto;

readonly class ProyectoDTO
{
    public function __construct(
        public string $codigo_proyecto,
        public string $denominacion,
        public ?string $unidad_medida = null,
        public ?string $anio_inicio = null,
        public ?string $anio_culm = null,
        public float $cant_programada_trim_i = 0,
        public float $cant_ejecutada_trim_i = 0,
        public float $cant_programada_trim_ii = 0,
        public float $cant_ejecutada_trim_ii = 0,
        public float $cant_programada_trim_iii = 0,
        public float $cant_ejecutada_trim_iii = 0,
        public float $cant_programada_trim_iv = 0,
        public float $cant_ejecutada_trim_iv = 0,
        public ?int $id_proyecto = null
    ) {
    }

    public static function fromModel(Proyecto $model): self
    {
        return new self(
            $model->codigo_proyecto,
            $model->denominacion,
            $model->unidad_medida,
            $model->anio_inicio,
            $model->anio_culm,
            $model->cant_programada_trim_i,
            $model->cant_ejecutada_trim_i,
            $model->cant_programada_trim_ii,
            $model->cant_ejecutada_trim_ii,
            $model->cant_programada_trim_iii,
            $model->cant_ejecutada_trim_iii,
            $model->cant_programada_trim_iv,
            $model->cant_ejecutada_trim_iv,
            $model->id_proyecto
        );
    }

    public function toArray(): array
    {
        return [
            'codigo_proyecto' => $this->codigo_proyecto,
            'denominacion' => $this->denominacion,
            'unidad_medida' => $this->unidad_medida,
            'anio_inicio' => $this->anio_inicio,
            'anio_culm' => $this->anio_culm,
            'cant_programada_trim_i' => $this->cant_programada_trim_i,
            'cant_ejecutada_trim_i' => $this->cant_ejecutada_trim_i,
            'cant_programada_trim_ii' => $this->cant_programada_trim_ii,
            'cant_ejecutada_trim_ii' => $this->cant_ejecutada_trim_ii,
            'cant_programada_trim_iii' => $this->cant_programada_trim_iii,
            'cant_ejecutada_trim_iii' => $this->cant_ejecutada_trim_iii,
            'cant_programada_trim_iv' => $this->cant_programada_trim_iv,
            'cant_ejecutada_trim_iv' => $this->cant_ejecutada_trim_iv,
            'id_proyecto' => $this->id_proyecto,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['codigo_proyecto'] ?? '',
            $data['denominacion'] ?? '',
            $data['unidad_medida'] ?? null,
            $data['anio_inicio'] ?? null,
            $data['anio_culm'] ?? null,
            (float)($data['cant_programada_trim_i'] ?? 0),
            (float)($data['cant_ejecutada_trim_i'] ?? 0),
            (float)($data['cant_programada_trim_ii'] ?? 0),
            (float)($data['cant_ejecutada_trim_ii'] ?? 0),
            (float)($data['cant_programada_trim_iii'] ?? 0),
            (float)($data['cant_ejecutada_trim_iii'] ?? 0),
            (float)($data['cant_programada_trim_iv'] ?? 0),
            (float)($data['cant_ejecutada_trim_iv'] ?? 0),
            $data['id_proyecto'] ?? null
        );
    }
}
