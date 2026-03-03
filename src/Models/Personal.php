<?php

namespace App\Models;

class Personal
{
    public function __construct(
        public int $codPersonal,
        public string $cedula,
        public string $nombres,
        public string $apellidos,
        public string $fechaNacimiento
    ) {
    }
}
