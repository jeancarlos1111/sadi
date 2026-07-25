<?php

declare(strict_types=1);

namespace App\Models;

readonly class Vacacion
{
    public function __construct(
        public int $codFicha,
        public string $fechaSalida,
        public string $fechaRetorno,
        public int $diasDisfrute,
        public int $diasBono,
        public float $montoVacaciones,
        public float $montoBono,
        public float $montoTotal,
        public string $estatus = 'Pagado',
        public bool $eliminado = false,
        public ?int $id = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_vacacion' => $this->id,
            'cod_ficha' => $this->codFicha,
            'fecha_salida' => $this->fechaSalida,
            'fecha_retorno' => $this->fechaRetorno,
            'dias_disfrute' => $this->diasDisfrute,
            'dias_bono' => $this->diasBono,
            'monto_vacaciones' => $this->montoVacaciones,
            'monto_bono' => $this->montoBono,
            'monto_total' => $this->montoTotal,
            'estatus' => $this->estatus,
            'eliminado' => $this->eliminado,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['cod_ficha'] ?? 0),
            $data['fecha_salida'] ?? '',
            $data['fecha_retorno'] ?? '',
            (int)($data['dias_disfrute'] ?? 0),
            (int)($data['dias_bono'] ?? 0),
            (float)($data['monto_vacaciones'] ?? 0.0),
            (float)($data['monto_bono'] ?? 0.0),
            (float)($data['monto_total'] ?? 0.0),
            $data['estatus'] ?? 'Pagado',
            (bool)($data['eliminado'] ?? false),
            isset($data['id_vacacion']) ? (int)$data['id_vacacion'] : null
        );
    }
}
