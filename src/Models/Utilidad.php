<?php

declare(strict_types=1);

namespace App\Models;

readonly class Utilidad
{
    public function __construct(
        public int $anio,
        public int $diasBase,
        public float $montoTotalNomina,
        public string $estatus,
        public bool $eliminado,
        public ?int $id = null
    ) {}
}
