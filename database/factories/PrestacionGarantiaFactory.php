<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Database\Factory;
use App\Models\PrestacionGarantia;
use App\Repositories\PrestacionesRepository;

class PrestacionGarantiaFactory extends Factory
{
    protected string $model = PrestacionGarantia::class;
    protected string $repository = PrestacionesRepository::class;

    public function definition(): array
    {
        return [
            'id' => null,
            'codFicha' => 1,
            'periodo' => '2025-Q1',
            'tipo' => 'TRIMESTRAL',
            'diasDepositados' => 15,
            'salarioIntegralDiario' => 0.0, // Should be calculated
            'monto' => 0.0,                 // Should be calculated
            'fechaProceso' => '2025-03-31',
            'eliminado' => false,
        ];
    }
}
