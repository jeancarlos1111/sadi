<?php
declare(strict_types=1);
namespace Database\Factories;

use App\Database\Factory;
use App\Models\MovimientoPresupuestario;
use App\Repositories\MovimientoPresupuestarioRepository;

class MovimientoPresupuestarioFactory extends Factory
{
    protected string $model = MovimientoPresupuestario::class;
    protected string $repository = MovimientoPresupuestarioRepository::class;

    public function definition(): array
    {
        return [
            'id_comprobante' => $this->faker->numberBetween(1, 5),
            'id_estruc_presupuestaria' => $this->faker->numberBetween(1, 5),
            'id_codigo_plan_unico' => $this->faker->numberBetween(1, 10),
            'id_operacion' => 'P',
            'monto_mp' => $this->faker->randomFloat(2, 100, 5000),
            'descripcion_mp' => 'Movimiento ' . $this->faker->word(),
        ];
    }
}
