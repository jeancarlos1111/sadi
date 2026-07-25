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
        public int $diasUtilidades = 30,
        public int $diasBonoVacacional = 15,
        public float $porcentajeIslr = 0.0,
        public bool $eliminado = false,
        public string $tipoRelacionLaboral = 'FIJO',
        public ?string $banco = null,
        public ?string $numeroCuenta = null,
        public string $tipoCuenta = 'CORRIENTE'
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
            'diasUtilidades' => $this->diasUtilidades,
            'diasBonoVacacional' => $this->diasBonoVacacional,
            'porcentajeIslr' => $this->porcentajeIslr,
            'eliminado' => $this->eliminado,
            'tipoRelacionLaboral' => $this->tipoRelacionLaboral,
            'banco' => $this->banco,
            'numeroCuenta' => $this->numeroCuenta,
            'tipoCuenta' => $this->tipoCuenta,
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
            $data['diasUtilidades'] ?? 30,
            $data['diasBonoVacacional'] ?? 15,
            $data['porcentajeIslr'] ?? 0.0,
            $data['eliminado'] ?? false,
            $data['tipoRelacionLaboral'] ?? 'FIJO',
            $data['banco'] ?? null,
            $data['numeroCuenta'] ?? null,
            $data['tipoCuenta'] ?? 'CORRIENTE'
        );
    }
}
