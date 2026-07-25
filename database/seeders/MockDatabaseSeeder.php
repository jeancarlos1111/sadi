<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Database\Seeder;
use Database\Factories\ProveedorFactory;
use Database\Factories\UsuarioFactory;
use Database\Factories\ProyectoFactory;
use Database\Factories\AccionCentralizadaFactory;
use Database\Factories\EstrucPresupuestariaFactory;
use Database\Factories\PresupuestoGastoFactory;
use Database\Factories\PresupuestoIngresoFactory;
use Database\Factories\ComprobantePresupuestarioFactory;
use Database\Factories\MovimientoPresupuestarioFactory;
use Database\Factories\PeriodoPresupuestarioFactory;

class MockDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds with fake data.
     *
     * @return void
     */
    public function run(): void
    {
        // 1. Cargar primero los datos semilla reales requeridos
        $this->call(DatabaseSeeder::class);

        // 2. Generar datos falsos usando Factories
        echo "Generando proveedores falsos (Faker)...\n";
        ProveedorFactory::new()->count(50)->create();

        echo "Generando beneficiarios falsos (Faker)...\n";
        \Database\Factories\BeneficiarioFactory::new()->count(50)->create();

        echo "Generando Proyectos (Faker)...\n";
        ProyectoFactory::new()->count(5)->create();

        echo "Generando Acciones Centralizadas (Faker)...\n";
        AccionCentralizadaFactory::new()->count(3)->create();

        echo "Generando Estructuras Presupuestarias (Faker)...\n";
        EstrucPresupuestariaFactory::new()->count(10)->create();

        echo "Generando Presupuesto de Gastos (Faker)...\n";
        PresupuestoGastoFactory::new()->count(20)->create();

        echo "Generando Periodos Presupuestarios (Faker)...\n";
        for ($i = 1; $i <= 12; $i++) {
            PeriodoPresupuestarioFactory::new()->create(['mes' => $i]);
        }

        echo "Generando Presupuesto de Ingresos (Faker)...\n";
        PresupuestoIngresoFactory::new()->count(5)->create();

        echo "Generando Comprobantes Presupuestarios (Faker)...\n";
        ComprobantePresupuestarioFactory::new()->count(10)->create();

        echo "Generando Movimientos Presupuestarios (Faker)...\n";
        MovimientoPresupuestarioFactory::new()->count(30)->create();

        echo "Generando Usuarios de prueba (Faker)...\n";
        UsuarioFactory::new()->count(5)->create();

        // Generar Personal, Ficha y Prestaciones de Prueba
        $this->call(PrestacionesSeeder::class);
    }
}
