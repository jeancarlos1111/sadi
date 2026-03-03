<?php

namespace App\Services\Taxes;

/**
 * Interfaz base para el Patrón Strategy en Retenciones.
 */
interface RetencionStrategy
{
    /**
     * Calcula y retorna el monto de la retención a aplicar.
     *
     * @param  float $baseImponible El monto base sobre el cual calcular
     * @param  float $porcentaje    El porcentaje a aplicar (ej: 75 para 75%)
     * @param  float $montoIva      El monto del impuesto IVA (necesario para retenciones de IVA)
     * @return float El monto total retenido
     */
    public function calcular(float $baseImponible, float $porcentaje, float $montoIva = 0.0): float;

    /**
     * Retorna el tipo de retención (ej: 'IVA', 'ISLR')
     */
    public function getTipo(): string;
}
