<?php

namespace App\Models;

class Banco
{
    public function __construct(
        public int $id,
        public string $nombreBanco
    ) {
    }
}
