<?php

declare(strict_types=1);

namespace App\Models;

readonly class IndicadorProyecto
{
    public function __construct(
        public int $id_proyecto,
        public ?string $indicador_eficacia = null,
        public ?string $indicador_eficiencia = null,
        public ?string $indicador_calidad = null,
        public ?string $indicador_impacto = null,
        public ?string $medio_verificacion = null,
        public ?int $id_indicador_proyecto = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_proyecto' => $this->id_proyecto,
            'indicador_eficacia' => $this->indicador_eficacia,
            'indicador_eficiencia' => $this->indicador_eficiencia,
            'indicador_calidad' => $this->indicador_calidad,
            'indicador_impacto' => $this->indicador_impacto,
            'medio_verificacion' => $this->medio_verificacion,
            'id_indicador_proyecto' => $this->id_indicador_proyecto,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id_proyecto'] ?? null,
            $data['indicador_eficacia'] ?? null,
            $data['indicador_eficiencia'] ?? null,
            $data['indicador_calidad'] ?? null,
            $data['indicador_impacto'] ?? null,
            $data['medio_verificacion'] ?? null,
            $data['id_indicador_proyecto'] ?? null,
        );
    }
}
