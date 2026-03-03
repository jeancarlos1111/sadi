<?php

namespace App\Models;

class PlanUnicoCuentas
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $codigo,
        public string $denominacion,
        ?int          $id = null
    ) {
        $this->id = $id;
    }
}
