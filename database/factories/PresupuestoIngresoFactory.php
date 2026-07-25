<?php
declare(strict_types=1);
namespace Database\Factories;

use App\Database\Factory;
use App\Models\PresupuestoIngreso;
use App\Repositories\PresupuestoIngresoRepository;

class PresupuestoIngresoFactory extends Factory
{
    protected string $model = PresupuestoIngreso::class;
    protected string $repository = PresupuestoIngresoRepository::class;

    public function definition(): array
    {
        return [
            'idRamo' => 1,
            'montoEstimado' => $this->faker->randomFloat(2, 10000, 100000),
            'montoRecaudado' => 0,
        ];
    }

    public function create(array $attributes = []): array|object
    {
        $results = [];
        $repo = new $this->repository();
        
        for ($i = 0; $i < $this->count; $i++) {
            $data = array_merge($this->definition(), $attributes);
            
            $repo->formular($data['idRamo'], $data['montoEstimado']);
            
            $modelClass = $this->model;
            $results[] = $modelClass::fromArray($data);
        }

        return $this->count === 1 ? $results[0] : $results;
    }
}
