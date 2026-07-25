<?php

declare(strict_types=1);

namespace App\Models;

class FondoAvanceReposicion
{
    public function __construct(
        public int $idFondo,
        public string $fechaReposicion,
        public float $montoRendido = 0.0,
        public string $estado = 'PENDIENTE',
        public ?int $idSolicitudPago = null,
        public ?int $idReposicion = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_reposicion' => $this->idReposicion,
            'id_fondo' => $this->idFondo,
            'fecha_reposicion' => $this->fechaReposicion,
            'monto_rendido' => $this->montoRendido,
            'estado' => $this->estado,
            'id_solicitud_pago' => $this->idSolicitudPago
        ];
    }
}
