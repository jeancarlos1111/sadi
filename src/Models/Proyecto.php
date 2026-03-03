<?php

namespace App\Models;

class Proyecto
{
    public /* private(set) */ ?int $id_proyecto;

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
        ?int $id_proyecto = null
    ) {
        $this->id_proyecto = $id_proyecto;
    }
}
