<?php

namespace App\Models;

class Servicio
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string  $denominacion,
        public ?string $descripcion,
        public int     $idTipoServicio,
        public bool    $aplicarIva = false,
        public ?int    $idCodigoPlanUnico = null,
        ?int           $id = null
    ) {
        $this->id = $id;
    }
}
