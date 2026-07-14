<?php

declare(strict_types=1);

namespace App\Models;

readonly class Ficha
{
    public function __construct(
        public int $id,
        public int $idPersonal,
        public int $idCargo,
        public int $idNomina,
        public string $fechaIngreso,
        public float $sueldoBasico,
        public bool $eliminado = false
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idPersonal' => $this->idPersonal,
            'idCargo' => $this->idCargo,
            'idNomina' => $this->idNomina,
            'fechaIngreso' => $this->fechaIngreso,
            'sueldoBasico' => $this->sueldoBasico,
            'eliminado' => $this->eliminado,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['idPersonal'] ?? null,
            $data['idCargo'] ?? null,
            $data['idNomina'] ?? null,
            $data['fechaIngreso'] ?? null,
            $data['sueldoBasico'] ?? null,
            $data['eliminado'] ?? null,
        );
    }
}
