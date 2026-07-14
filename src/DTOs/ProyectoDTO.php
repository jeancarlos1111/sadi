<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Proyecto;

class ProyectoDTO
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
        public float $cant_ejecutada_trim_iv = 0
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
            $model->cant_ejecutada_trim_iv
        );
    }
}
