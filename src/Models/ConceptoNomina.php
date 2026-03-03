<?php

namespace App\Models;

class ConceptoNomina
{
    public function __construct(
        public string $codigo,
        public string $descripcion,
        public string $tipo, // 'A' Asignacion, 'D' Deduccion
        public float $formulaValor,
        public bool $esPorcentaje,
        public ?string $formulaExpr = null, // Expresión algebraica dinámica
        public ?int $id = null
    ) {
    }
}
