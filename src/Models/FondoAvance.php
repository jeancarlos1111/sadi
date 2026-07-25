<?php

declare(strict_types=1);

namespace App\Models;

class FondoAvance
{
    public function __construct(
        public string $denominacion,
        public float $montoMaximo,
        public string $responsableCedula,
        public string $fechaCreacion,
        public string $estado = 'ACTIVO',
        public ?int $idCuentaContable = null,
        public ?int $idFondo = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_fondo' => $this->idFondo,
            'denominacion' => $this->denominacion,
            'monto_maximo' => $this->montoMaximo,
            'responsable_cedula' => $this->responsableCedula,
            'fecha_creacion' => $this->fechaCreacion,
            'estado' => $this->estado,
            'id_cuenta_contable' => $this->idCuentaContable
        ];
    }
}
