<?php

namespace App\Models;

class RequisicionBienes
{
    // Property Hooks
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $fecha,
        public string $concepto,
        public int $idEstructuraPresupuestaria,
        public array $articulos = [], // Array of associative arrays: ['id_articulo' => X, 'cantidad' => Y]
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
