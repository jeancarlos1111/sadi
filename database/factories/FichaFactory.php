<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Database\Factory;
use App\Models\Ficha;
use App\Repositories\FichaRepository;

class FichaFactory extends Factory
{
    protected string $model = Ficha::class;
    protected string $repository = FichaRepository::class;

    public function definition(): array
    {
        return [
            'id'                 => 0, // Assigned by DB
            'idPersonal'         => 1, // Must be overridden when created
            'idCargo'            => 1, // Assuming cargo 1 is set up by seeders
            'idNomina'           => 1, // Assuming nomina 1 is set up
            'fechaIngreso'       => $this->faker->dateTimeBetween('-10 years', '-1 years')->format('Y-m-d'),
            'sueldoBasico'       => $this->faker->randomFloat(2, 3000, 15000),
            'diasUtilidades'     => 30,
            'diasBonoVacacional' => 15,
            'porcentajeIslr'     => $this->faker->randomFloat(2, 0, 15),
            'eliminado'          => false,
        ];
    }

    public function create(array $attributes = []): array|object
    {
        $results = [];
        $repo = new FichaRepository();

        for ($i = 0; $i < $this->count; $i++) {
            $data = array_merge($this->definition(), $attributes);
            
            $modelInstance = Ficha::fromArray($data);
            $id = $repo->save($modelInstance);
            
            if (is_int($id) && $id > 0) {
                $data['id'] = $id;
                $modelInstance = Ficha::fromArray($data);
            }
            
            $results[] = $modelInstance;
        }

        return $this->count === 1 ? ($results[0] ?? null) : $results;
    }
}
