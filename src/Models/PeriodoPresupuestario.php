<?php

namespace App\Models;

class PeriodoPresupuestario
{
    public const MESES = [
        1  => 'Enero',   2  => 'Febrero',  3  => 'Marzo',
        4  => 'Abril',   5  => 'Mayo',     6  => 'Junio',
        7  => 'Julio',   8  => 'Agosto',   9  => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre',12 => 'Diciembre',
    ];

    public ?int $id_periodo;

    public function __construct(
        public int $anio,
        public int $mes,
        public string $estado = 'ABIERTO',
        public ?string $fecha_cierre = null,
        public ?string $observacion = null,
        ?int $id_periodo = null
    ) {
        $this->id_periodo = $id_periodo;
    }

    public function isAbierto(): bool
    {
        return $this->estado === 'ABIERTO';
    }

    public function nombreMes(): string
    {
        return self::MESES[$this->mes] ?? "Mes $this->mes";
    }
}
