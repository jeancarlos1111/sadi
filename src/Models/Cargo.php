<?php

namespace App\Models;

class Cargo
{
    public function __construct(
        public int $id,
        public string $nombre
    ) {
    }
}
