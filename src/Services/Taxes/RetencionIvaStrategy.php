<?php

namespace App\Services\Taxes;

class RetencionIvaStrategy implements RetencionStrategy
{
    public function calcular(float $baseImponible, float $porcentaje, float $montoIva = 0.0): float
    {
        // La retención de IVA se calcula sobre el monto del IVA facturado, no sobre la base imponible general
        return $montoIva * ($porcentaje / 100);
    }

    public function getTipo(): string
    {
        return 'IVA';
    }
}
