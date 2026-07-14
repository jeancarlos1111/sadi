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
        public string $direccion,
        public string $telefono,
        public ?string $nit = null,
        public ?int $idCodigoContable = null
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
            $model->idCodigoContable
        );
    }
}
