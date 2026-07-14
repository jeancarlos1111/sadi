<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\PresupuestoGasto;

class PresupuestoGastoDTO
{
    public function __construct(
        public ?int $id,
        public int $idEstructura,
        public int $idPlanUnico,
        public float $montoAsignado,
        public float $montoComprometido = 0,
        public float $montoCausado = 0,
        public float $montoPagado = 0,
        public ?int $idFuenteFinanciamiento = null,
        public ?int $idUnidadAdministrativa = null
    ) {
    }

    public static function fromModel(PresupuestoGasto $model): self
    {
        return new self(
            $model->id,
            $model->idEstructura,
            $model->idPlanUnico,
            $model->montoAsignado,
            $model->montoComprometido,
            $model->montoCausado,
            $model->montoPagado,
            $model->idFuenteFinanciamiento,
            $model->idUnidadAdministrativa
        );
    }
}
