<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\PeriodoPresupuestario;

readonly class PeriodoPresupuestarioDTO
{
    public const MESES = [
        1  => 'Enero',   2  => 'Febrero',  3  => 'Marzo',
        4  => 'Abril',   5  => 'Mayo',     6  => 'Junio',
        7  => 'Julio',   8  => 'Agosto',   9  => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre',12 => 'Diciembre',
    ];

    public function __construct(
        public ?int $id_periodo,
        public int $anio,
        public int $mes,
        public string $estado = 'ABIERTO',
        public ?string $fecha_cierre = null,
        public ?string $observacion = null
    ) {
    }

    public function isAbierto(): bool
    {
        return $this->estado === 'ABIERTO';
    }

    public function nombreMes(): string
    {
        return self::MESES[$this->mes] ?? "Mes $this->mes";
    }

    public static function fromModel(PeriodoPresupuestario $model): self
    {
        return new self(
            $model->id_periodo,
            $model->anio,
            $model->mes,
            $model->estado,
            $model->fecha_cierre,
            $model->observacion
        );
    }
}
