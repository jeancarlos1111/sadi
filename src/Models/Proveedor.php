<?php

namespace App\Models;

class Proveedor
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $rif,
        public string $compania,
        public int $idTipoOrganizacion,
        public string $direccion,
        public string $telefono,
        public ?string $nit = null,
        public ?int $idCodigoContable = null,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
