<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Database\Factory;
use App\Models\Personal;
use App\Repositories\PersonalRepository;

class PersonalFactory extends Factory
{
    protected string $model = Personal::class;
    protected string $repository = PersonalRepository::class;

    public function definition(): array
    {
        return [
            'codPersonal'     => 0, // Assigned by DB
            'cedula'          => 'V-' . $this->faker->unique()->randomNumber(8, true),
            'nombres'         => $this->faker->firstName(),
            'apellidos'       => $this->faker->lastName(),
            'fechaNacimiento' => $this->faker->dateTimeBetween('-50 years', '-20 years')->format('Y-m-d'),
        ];
    }

    public function create(array $attributes = []): array|object
    {
        $results = [];
        $repo = new PersonalRepository();
        $pdo = $repo->getPdo();

        for ($i = 0; $i < $this->count; $i++) {
            $data = array_merge($this->definition(), $attributes);
            
            $modelInstance = Personal::fromArray($data);
            $repo->save($modelInstance);
            
            // Re-fetch to get the assigned ID
            $stmt = $pdo->prepare("SELECT cod_personal FROM personal WHERE cedula = ?");
            $stmt->execute([$data['cedula']]);
            $id = $stmt->fetchColumn();
            
            if ($id) {
                $data['codPersonal'] = (int)$id;
                $modelInstance = Personal::fromArray($data);
            }
            
            $results[] = $modelInstance;
        }

        return $this->count === 1 ? ($results[0] ?? null) : $results;
    }
}
