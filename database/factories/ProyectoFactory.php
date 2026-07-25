<?php
declare(strict_types=1);
namespace Database\Factories;

use App\Database\Factory;
use App\Models\Proyecto;
use App\Repositories\ProyectoRepository;

class ProyectoFactory extends Factory
{
    protected string $model = Proyecto::class;
    protected string $repository = ProyectoRepository::class;

    public function definition(): array
    {
        return [
            'codigo_proyecto' => 'PRY-' . $this->faker->numerify('####'),
            'denominacion' => $this->faker->sentence(3),
            'unidad_medida' => 'Unidad',
            'anio_inicio' => '2026',
            'anio_culm' => '2027',
            'cant_programada_trim_i' => $this->faker->randomFloat(2, 10, 100),
            'cant_ejecutada_trim_i' => 0,
            'cant_programada_trim_ii' => $this->faker->randomFloat(2, 10, 100),
            'cant_ejecutada_trim_ii' => 0,
            'cant_programada_trim_iii' => $this->faker->randomFloat(2, 10, 100),
            'cant_ejecutada_trim_iii' => 0,
            'cant_programada_trim_iv' => $this->faker->randomFloat(2, 10, 100),
            'cant_ejecutada_trim_iv' => 0,
            'indicador_eficacia' => 'Eficacia ' . $this->faker->word(),
            'indicador_eficiencia' => 'Eficiencia ' . $this->faker->word(),
            'indicador_calidad' => 'Calidad ' . $this->faker->word(),
            'indicador_impacto' => 'Impacto ' . $this->faker->word(),
            'medio_verificacion' => 'Reporte trimestral',
            'id_unidad_administrativa' => 3, // Asume que RRHH o alguna existe
        ];
    }
}
