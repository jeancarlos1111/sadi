<?php

namespace App\Models;

class TipoOrganizacion
{
    public function __construct(
        public int $id,
        public string $nombre
    ) {
    }
}
