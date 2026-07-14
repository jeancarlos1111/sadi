<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\IndicadorAccionCentralizada;

class IndicadorAccionCentralizadaDTO
{
    public function __construct(
        public int $id_accion_centralizada,
        public ?string $indicador_eficacia = null,
        public ?string $indicador_eficiencia = null,
        public ?string $indicador_calidad = null,
        public ?string $indicador_impacto = null,
        public ?string $medio_verificacion = null
    ) {
    }

    public static function fromModel(IndicadorAccionCentralizada $model): self
    {
        return new self(
            $model->id_accion_centralizada,
            $model->indicador_eficacia,
            $model->indicador_eficiencia,
            $model->indicador_calidad,
            $model->indicador_impacto,
            $model->medio_verificacion
        );
    }
}
