<?php

declare(strict_types=1);

namespace App\Models;

class TomaInventario
{
    public function __construct(
        public string $fechaToma,
        public string $responsable,
        public string $estado = 'ABIERTA',
        public ?string $observaciones = null,
        public ?string $fechaCierre = null,
        public ?int $idToma = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_toma' => $this->idToma,
            'fecha_toma' => $this->fechaToma,
            'responsable' => $this->responsable,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'fecha_cierre' => $this->fechaCierre
        ];
    }
}
