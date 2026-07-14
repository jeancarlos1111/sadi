<?php

declare(strict_types=1);

namespace App\Models;

readonly class PeriodoPresupuestario
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

    public function toArray(): array
    {
        return [
            'id_periodo' => $this->id_periodo,
            'anio' => $this->anio,
            'mes' => $this->mes,
            'estado' => $this->estado,
            'fecha_cierre' => $this->fecha_cierre,
            'observacion' => $this->observacion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['anio'] ?? null,
            $data['mes'] ?? null,
            $data['estado'] ?? null,
            $data['fecha_cierre'] ?? null,
            $data['observacion'] ?? null,
            $data['id_periodo'] ?? null,
        );
    }
}
