<?php

declare(strict_types=1);

namespace App\Models;

readonly class PresupuestoGasto
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public int $idEstructura,
        public int $idPlanUnico,
        public float $montoAsignado,
        public float $montoComprometido = 0,
        public float $montoPrecomprometido = 0,
        public float $montoCausado = 0,
        public float $montoPagado = 0,
        public ?int $idFuenteFinanciamiento = null,
        public ?int $idUnidadAdministrativa = null,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idEstructura' => $this->idEstructura,
            'idPlanUnico' => $this->idPlanUnico,
            'montoAsignado' => $this->montoAsignado,
            'montoComprometido' => $this->montoComprometido,
            'montoPrecomprometido' => $this->montoPrecomprometido,
            'montoCausado' => $this->montoCausado,
            'montoPagado' => $this->montoPagado,
            'idFuenteFinanciamiento' => $this->idFuenteFinanciamiento,
            'idUnidadAdministrativa' => $this->idUnidadAdministrativa,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idEstructura'] ?? null,
            $data['idPlanUnico'] ?? null,
            $data['montoAsignado'] ?? null,
            $data['montoComprometido'] ?? null,
            $data['montoPrecomprometido'] ?? null,
            $data['montoCausado'] ?? null,
            $data['montoPagado'] ?? null,
            $data['idFuenteFinanciamiento'] ?? null,
            $data['idUnidadAdministrativa'] ?? null,
            $data['id'] ?? null,
        );
    }
}
