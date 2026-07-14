<?php

declare(strict_types=1);

namespace App\Models;

readonly class RecepcionAlmacen
{
    // Esta clase funcionaba más como un DTO/Helper transaccional puro.
    // Como entidad puede estar vacía o representar el registro de recepción si la base de datos tuviera tabla propia.
    // En este caso, simplemente queda como namespace limpio por si requiere ser instanciada en el futuro.
}
