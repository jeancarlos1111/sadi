<?php
declare(strict_types=1);
namespace Database\Factories;

use App\Database\Factory;
use App\Models\AccionCentralizada;
use App\Repositories\AccionCentralizadaRepository;

class AccionCentralizadaFactory extends Factory
{
    protected string $model = AccionCentralizada::class;
    protected string $repository = AccionCentralizadaRepository::class;

    public function definition(): array
    {
        return [
            'codigo_accion_centralizada' => 'AC-' . $this->faker->numerify('####'),
            'denominacion' => $this->faker->sentence(4),
            'unidad_medida' => 'Servicio',
            'anio_inicio' => '2026',
            'anio_culm' => '2026',
            'cant_programada_trim_i' => $this->faker->randomFloat(2, 50, 200),
            'cant_ejecutada_trim_i' => 0,
            'cant_programada_trim_ii' => $this->faker->randomFloat(2, 50, 200),
            'cant_ejecutada_trim_ii' => 0,
            'cant_programada_trim_iii' => $this->faker->randomFloat(2, 50, 200),
            'cant_ejecutada_trim_iii' => 0,
            'cant_programada_trim_iv' => $this->faker->randomFloat(2, 50, 200),
            'cant_ejecutada_trim_iv' => 0,
            'indicador_eficacia' => 'Eficacia ' . $this->faker->word(),
            'indicador_eficiencia' => 'Eficiencia ' . $this->faker->word(),
            'indicador_calidad' => 'Calidad ' . $this->faker->word(),
            'indicador_impacto' => 'Impacto ' . $this->faker->word(),
            'medio_verificacion' => 'Auditoría interna',
            'id_unidad_administrativa' => 3,
        ];
    }
}
