<?php

namespace App\Models;

class Beneficiario
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string  $cedula,
        public string  $nombres,
        public string  $apellidos,
        public string  $direccion,
        public string  $telefono,
        public ?string $email = null,
        public ?int    $idCodigoContable = null,
        ?int           $id = null
    ) {
        $this->id = $id;
    }
}
