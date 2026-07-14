<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\ConceptoNomina;

class ConceptoNominaDTO
{
    public function __construct(
        public string $codigo,
        public string $descripcion,
        public string $tipo,
        public float $formulaValor,
        public bool $esPorcentaje,
        public ?string $formulaExpr = null,
        public ?int $id = null
    ) {
    }

    public static function fromModel(ConceptoNomina $model): self
    {
        return new self(
            $model->codigo,
            $model->descripcion,
            $model->tipo,
            $model->formulaValor,
            $model->esPorcentaje,
            $model->formulaExpr,
            $model->id
        );
    }
}
