<?php

namespace App\Models;

class AsientoContable
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
}
