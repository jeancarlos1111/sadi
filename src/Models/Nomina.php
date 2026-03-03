<?php

namespace App\Models;

class Nomina
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $tipoPeriodo
    ) {
    }
}
