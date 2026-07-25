<?php

declare(strict_types=1);

namespace App\Models;

class FondoAvanceGasto
{
    public function __construct(
        public int $idReposicion,
        public string $fechaGasto,
        public string $concepto,
        public float $monto,
        public ?string $facturaNum = null,
        public ?string $proveedorRif = null,
        public ?int $idGasto = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_gasto' => $this->idGasto,
            'id_reposicion' => $this->idReposicion,
            'fecha_gasto' => $this->fechaGasto,
            'concepto' => $this->concepto,
            'monto' => $this->monto,
            'factura_num' => $this->facturaNum,
            'proveedor_rif' => $this->proveedorRif
        ];
    }
}
