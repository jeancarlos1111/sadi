<?php

namespace App\Services\Taxes;

class RetencionIslrStrategy implements RetencionStrategy
{
    public function calcular(float $baseImponible, float $porcentaje, float $montoIva = 0.0): float
    {
        // La retención de ISLR se calcula sobre la base imponible del servicio/bien
        return $baseImponible * ($porcentaje / 100);
    }

    public function getTipo(): string
    {
        return 'ISLR';
    }
}
