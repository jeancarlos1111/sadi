<?php

declare(strict_types=1);

namespace App\Models;

readonly class OfertaProveedor
{
    public function __construct(
        public int $id,
        public int $idProceso,
        public int $idProveedor,
        public string $fechaPresentacion,
        public float $montoOfertado,
        public ?string $descripcionOferta = null,
        public bool $cumpleTecnicamente = true,
        public bool $esGanador = false,
        public ?string $observaciones = null,
        public bool $eliminado = false
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idProceso' => $this->idProceso,
            'idProveedor' => $this->idProveedor,
            'fechaPresentacion' => $this->fechaPresentacion,
            'montoOfertado' => $this->montoOfertado,
            'descripcionOferta' => $this->descripcionOferta,
            'cumpleTecnicamente' => $this->cumpleTecnicamente,
            'esGanador' => $this->esGanador,
            'observaciones' => $this->observaciones,
            'eliminado' => $this->eliminado,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? 0,
            $data['idProceso'] ?? 0,
            $data['idProveedor'] ?? 0,
            $data['fechaPresentacion'] ?? '',
            (float)($data['montoOfertado'] ?? 0),
            $data['descripcionOferta'] ?? null,
            (bool)($data['cumpleTecnicamente'] ?? true),
            (bool)($data['esGanador'] ?? false),
            $data['observaciones'] ?? null,
            (bool)($data['eliminado'] ?? false)
        );
    }
}
