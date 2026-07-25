<?php
declare(strict_types=1);
namespace Database\Factories;

use App\Database\Factory;
use App\Models\EstructuraPresupuestaria;
use App\Repositories\EstrucPresupuestariaRepository;

class EstrucPresupuestariaFactory extends Factory
{
    protected string $model = EstructuraPresupuestaria::class;
    protected string $repository = EstrucPresupuestariaRepository::class;

    public function definition(): array
    {
        return [
            'id' => 0,
            'descripcion' => 'Estructura ' . $this->faker->word(),
        ];
    }
}
