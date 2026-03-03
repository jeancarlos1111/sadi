<?php

namespace App\Models;

class Usuario
{
    public function __construct(
        public int $id,
        public string $usuario,
        public ?int $cedulaPersonal
    ) {
    }
}
