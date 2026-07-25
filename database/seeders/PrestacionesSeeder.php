<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Database\Seeder;

class PrestacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        echo "Generando Personal y Prestaciones mediante Factories...\n";
        
        $db = \App\Database\Connection::getInstance();
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            // Generate 2000 Personal records
            for ($i = 0; $i < 2000; $i++) {
                $personal = \Database\Factories\PersonalFactory::new()->create();
                
                $ficha = \Database\Factories\FichaFactory::new()->create([
                    'idPersonal' => $personal->codPersonal,
                    'idCargo' => 1,
                    'idNomina' => 1,
                ]);

                // Calculate Salary
                $sueldoDiario = $ficha->sueldoBasico / 30;
                $salarioIntegral = $sueldoDiario + (($sueldoDiario * 30)/360) + (($sueldoDiario * 15)/360);
                $salarioIntegral = round($salarioIntegral, 2);
                $monto = round($salarioIntegral * 15, 2);

                $periodos = ['2025-Q1', '2025-Q2', '2025-Q3', '2025-Q4'];
                foreach ($periodos as $periodo) {
                    $fechaProceso = match ($periodo) {
                        '2025-Q1' => '2025-03-31',
                        '2025-Q2' => '2025-06-30',
                        '2025-Q3' => '2025-09-30',
                        '2025-Q4' => '2025-12-31',
                    };

                    \Database\Factories\PrestacionGarantiaFactory::new()->create([
                        'codFicha' => $ficha->id,
                        'periodo' => $periodo,
                        'salarioIntegralDiario' => $salarioIntegral,
                        'monto' => $monto,
                        'fechaProceso' => $fechaProceso,
                    ]);
                }
            }
            if (!$inTransaction) $db->commit();
        } catch (\Exception $e) {
            if (!$inTransaction) $db->rollBack();
            echo "Error seeding Prestaciones: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
