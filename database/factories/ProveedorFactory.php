<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Database\Factory;
use App\Models\Proveedor;
use App\Repositories\ProveedorRepository;

class ProveedorFactory extends Factory
{
    protected string $model = Proveedor::class;
    protected string $repository = ProveedorRepository::class;

    public function definition(): array
    {
        return [
            'rif' => 'J-' . $this->faker->numerify('########') . '-' . $this->faker->randomDigit(),
            'compania' => $this->faker->company(),
            'idTipoOrganizacion' => $this->faker->numberBetween(1, 3), // Asume ID 1 a 3 existentes (Firma, CA, SA)
            'direccion' => $this->faker->address(),
            'telefono' => $this->faker->numerify('0414#######'),
            'nit' => null,
            'idCodigoContable' => null,
            'numeroRnc' => $this->faker->numerify('RNC-#########'),
            'fechaVencimientoRnc' => $this->faker->dateTimeBetween('-1 year', '+2 years')->format('Y-m-d'),
        ];
    }
}
