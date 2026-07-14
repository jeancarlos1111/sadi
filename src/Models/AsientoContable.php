<?php

declare(strict_types=1);

namespace App\Models;

readonly class AsientoContable
{
    public function __construct(
        public string $numeroComprobante,
        public string $fecha,
        public string $concepto,
        public float $totalDebe,
        public float $totalHaber,
        public ?int $id = null
    ) {
    }

    /**
     * Mantener método estático para compatibilidad temporal con módulos no migrados,
     * pero delegando la lógica al Repositorio.
     * NOTA: Se recomienda inyectar AsientoContableRepository en el futuro.
     */
    public static function registrarDesdeTransaccion(string $fecha, string $concepto, array $movimientos): bool
    {
        $repo = new \App\Repositories\AsientoContableRepository();

        return $repo->registrarDesdeTransaccion($fecha, $concepto, $movimientos);
    }

    public function toArray(): array
    {
        return [
            'numeroComprobante' => $this->numeroComprobante,
            'fecha' => $this->fecha,
            'concepto' => $this->concepto,
            'totalDebe' => $this->totalDebe,
            'totalHaber' => $this->totalHaber,
            'id' => $this->id,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['numeroComprobante'] ?? null,
            $data['fecha'] ?? null,
            $data['concepto'] ?? null,
            $data['totalDebe'] ?? null,
            $data['totalHaber'] ?? null,
            $data['id'] ?? null,
        );
    }
}
