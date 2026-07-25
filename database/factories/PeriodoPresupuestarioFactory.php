<?php
declare(strict_types=1);
namespace Database\Factories;

use App\Database\Factory;
use App\Models\PeriodoPresupuestario;
use App\Repositories\PeriodoPresupuestarioRepository;

class PeriodoPresupuestarioFactory extends Factory
{
    protected string $model = PeriodoPresupuestario::class;
    protected string $repository = PeriodoPresupuestarioRepository::class;

    public function definition(): array
    {
        return [
            'anio' => 2026,
            'mes' => $this->faker->numberBetween(1, 12),
            'estado' => 'ABIERTO',
            'fecha_cierre' => null,
            'observacion' => 'Periodo abierto',
        ];
    }

    public function create(array $attributes = []): array|object
    {
        $results = [];
        $repo = new $this->repository();
        
        for ($i = 0; $i < $this->count; $i++) {
            $data = array_merge($this->definition(), $attributes);
            
            $repo->actualizarEstados($data['anio'], [$data['mes'] => $data['estado']], $data['observacion']);
            
            $modelClass = $this->model;
            $results[] = $modelClass::fromArray($data);
        }

        return $this->count === 1 ? $results[0] : $results;
    }
}
