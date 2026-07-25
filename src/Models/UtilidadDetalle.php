<?php

declare(strict_types=1);

namespace App\Models;

readonly class UtilidadDetalle
{
    public function __construct(
        public int $idUtilidad,
        public int $codFicha,
        public string $fechaIngresoCalculo,
        public int $mesesLaborados,
        public float $salarioBase,
        public float $montoPagar,
        public ?int $id = null
    ) {}
}
