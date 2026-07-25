<?php
declare(strict_types=1);
namespace Database\Factories;

use App\Database\Factory;
use App\Models\PresupuestoGasto;
use App\Repositories\PresupuestoGastoRepository;

class PresupuestoGastoFactory extends Factory
{
    protected string $model = PresupuestoGasto::class;
    protected string $repository = PresupuestoGastoRepository::class;

    public function definition(): array
    {
        return [
            'idEstructura' => $this->faker->numberBetween(1, 5),
            'idPlanUnico' => $this->faker->numberBetween(1, 10),
            'montoAsignado' => $this->faker->randomFloat(2, 1000, 50000),
            'montoComprometido' => 0,
            'montoPrecomprometido' => 0,
            'montoCausado' => 0,
            'montoPagado' => 0,
            'idFuenteFinanciamiento' => $this->faker->numberBetween(1, 4),
            'idUnidadAdministrativa' => 3,
        ];
    }
}
