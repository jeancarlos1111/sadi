<?php

namespace App\Models;

class EstrucPresupuestaria
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $descripcion,
        public int $idAccionesCentralizadas = 0,
        public int $idAccionEspecifica = 0,
        public int $idOtrasAcciones = 0,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
