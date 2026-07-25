<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Database\Factory;
use App\Models\Beneficiario;
use App\Repositories\BeneficiarioRepository;

class BeneficiarioFactory extends Factory
{
    protected string $model = Beneficiario::class;
    protected string $repository = BeneficiarioRepository::class;

    public function definition(): array
    {
        return [
            'cedula' => 'V-' . $this->faker->unique()->numerify('########'),
            'nombres' => $this->faker->firstName(),
            'apellidos' => $this->faker->lastName(),
            'direccion' => $this->faker->address(),
            'telefono' => $this->faker->numerify('0414#######'),
            'email' => $this->faker->unique()->safeEmail(),
            'idCodigoContable' => null,
        ];
    }
}
