<?php

declare(strict_types=1);

namespace App\Models;

readonly class ConceptoNomina
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

    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'tipo' => $this->tipo,
            'formulaValor' => $this->formulaValor,
            'esPorcentaje' => $this->esPorcentaje,
            'formulaExpr' => $this->formulaExpr,
            'id' => $this->id,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['codigo'] ?? null,
            $data['descripcion'] ?? null,
            $data['tipo'] ?? null,
            $data['formulaValor'] ?? null,
            $data['esPorcentaje'] ?? null,
            $data['formulaExpr'] ?? null,
            $data['id'] ?? null,
        );
    }
}
