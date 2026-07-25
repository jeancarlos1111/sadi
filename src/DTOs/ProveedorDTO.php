<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Proveedor;

class ProveedorDTO
{
    public function __construct(
        public ?int $id,
        public string $rif,
        public string $compania,
        public int $idTipoOrganizacion,
        public ?string $direccion = null,
        public ?string $telefono = null,
        public ?string $nit = null,
        public ?int $idCodigoContable = null,
        public ?string $numeroRnc = null,
        public ?string $fechaVencimientoRnc = null
    ) {
    }

    public static function fromModel(Proveedor $model): self
    {
        return new self(
            $model->id,
            $model->rif,
            $model->compania,
            $model->idTipoOrganizacion,
            $model->direccion,
            $model->telefono,
            $model->nit,
            $model->idCodigoContable,
            $model->numeroRnc,
            $model->fechaVencimientoRnc
        );
    }
}
