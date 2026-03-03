<?php

namespace App\Models;

class Articulo
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $denominacion,
        public string $observacion,
        public int $idTipoDeArticulo,
        public int $idUnidadesDeMedida,
        public ?int $idCodigoPlanUnico,
        public bool $aplicarIva,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
