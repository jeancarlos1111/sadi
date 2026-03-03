<?php

namespace App\Models;

class Ficha
{
    public function __construct(
        public int $id,
        public int $idPersonal,
        public int $idCargo,
        public int $idNomina,
        public string $fechaIngreso,
        public float $sueldoBasico,
        public bool $eliminado = false
    ) {
    }
}
