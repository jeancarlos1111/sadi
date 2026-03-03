<?php

namespace App\Models;

class TipoOperacionBancaria
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $acronimo
    ) {
    }
}
