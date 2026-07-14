<?php

declare(strict_types=1);

namespace App\Models;

readonly class Proyecto
{
    /* private(set) */ public ?int $id_proyecto;

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
        public ?string $indicador_eficacia = null,
        public ?string $indicador_eficiencia = null,
        public ?string $indicador_calidad = null,
        public ?string $indicador_impacto = null,
        public ?string $medio_verificacion = null,
        public ?int $id_unidad_administrativa = null,
        ?int $id_proyecto = null
    ) {
        $this->id_proyecto = $id_proyecto;
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
            'indicador_eficacia' => $this->indicador_eficacia,
            'indicador_eficiencia' => $this->indicador_eficiencia,
            'indicador_calidad' => $this->indicador_calidad,
            'indicador_impacto' => $this->indicador_impacto,
            'medio_verificacion' => $this->medio_verificacion,
            'id_unidad_administrativa' => $this->id_unidad_administrativa,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['codigo_proyecto'] ?? null,
            $data['denominacion'] ?? null,
            $data['unidad_medida'] ?? null,
            $data['anio_inicio'] ?? null,
            $data['anio_culm'] ?? null,
            $data['cant_programada_trim_i'] ?? null,
            $data['cant_ejecutada_trim_i'] ?? null,
            $data['cant_programada_trim_ii'] ?? null,
            $data['cant_ejecutada_trim_ii'] ?? null,
            $data['cant_programada_trim_iii'] ?? null,
            $data['cant_ejecutada_trim_iii'] ?? null,
            $data['cant_programada_trim_iv'] ?? null,
            $data['cant_ejecutada_trim_iv'] ?? null,
            $data['indicador_eficacia'] ?? null,
            $data['indicador_eficiencia'] ?? null,
            $data['indicador_calidad'] ?? null,
            $data['indicador_impacto'] ?? null,
            $data['medio_verificacion'] ?? null,
            $data['id_unidad_administrativa'] ?? null,
            $data['id_proyecto'] ?? null,
        );
    }
}
