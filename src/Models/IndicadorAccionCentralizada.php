<?php

declare(strict_types=1);

namespace App\Models;

readonly class IndicadorAccionCentralizada
{
    public function __construct(
        public int $id_accion_centralizada,
        public ?string $indicador_eficacia = null,
        public ?string $indicador_eficiencia = null,
        public ?string $indicador_calidad = null,
        public ?string $indicador_impacto = null,
        public ?string $medio_verificacion = null,
        public ?int $id_indicador_ac = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_accion_centralizada' => $this->id_accion_centralizada,
            'indicador_eficacia' => $this->indicador_eficacia,
            'indicador_eficiencia' => $this->indicador_eficiencia,
            'indicador_calidad' => $this->indicador_calidad,
            'indicador_impacto' => $this->indicador_impacto,
            'medio_verificacion' => $this->medio_verificacion,
            'id_indicador_ac' => $this->id_indicador_ac,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id_accion_centralizada'] ?? null,
            $data['indicador_eficacia'] ?? null,
            $data['indicador_eficiencia'] ?? null,
            $data['indicador_calidad'] ?? null,
            $data['indicador_impacto'] ?? null,
            $data['medio_verificacion'] ?? null,
            $data['id_indicador_ac'] ?? null,
        );
    }
}
