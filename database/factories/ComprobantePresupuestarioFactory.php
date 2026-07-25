<?php
declare(strict_types=1);
namespace Database\Factories;

use App\Database\Factory;
use App\Models\ComprobantePresupuestario;
use App\Repositories\ComprobantePresupuestarioRepository;

class ComprobantePresupuestarioFactory extends Factory
{
    protected string $model = ComprobantePresupuestario::class;
    protected string $repository = ComprobantePresupuestarioRepository::class;

    public function definition(): array
    {
        return [
            'acronimo_c' => $this->faker->randomElement(['COMP', 'PRE', 'CAUS', 'PAG']),
            'numero_c' => $this->faker->numerify('####-2026'),
            'fecha_c' => '2026-05-15',
            'denominacion_c' => 'Comprobante de ' . $this->faker->word(),
            'referencia_c' => $this->faker->numerify('REF-####'),
            'beneficiario_cedula' => null,
            'estado' => 'APROBADO',
        ];
    }
}
