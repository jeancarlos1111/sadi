<?php

declare(strict_types=1);

namespace App\Models;

readonly class PrestacionGarantia
{
    public function __construct(
        public ?int $id,
        public int $codFicha,
        public string $periodo,
        public string $tipo,
        public int $diasDepositados,
        public float $salarioIntegralDiario,
        public float $monto,
        public string $fechaProceso,
        public bool $eliminado = false
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codFicha' => $this->codFicha,
            'periodo' => $this->periodo,
            'tipo' => $this->tipo,
            'diasDepositados' => $this->diasDepositados,
            'salarioIntegralDiario' => $this->salarioIntegralDiario,
            'monto' => $this->monto,
            'fechaProceso' => $this->fechaProceso,
            'eliminado' => $this->eliminado,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            (int)$data['codFicha'],
            $data['periodo'],
            $data['tipo'],
            (int)$data['diasDepositados'],
            (float)$data['salarioIntegralDiario'],
            (float)$data['monto'],
            $data['fechaProceso'],
            $data['eliminado'] ?? false
        );
    }
}
