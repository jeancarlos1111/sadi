<?php

declare(strict_types=1);

namespace App\Models;

readonly class ActaRecepcionDetalle
{
    public function __construct(
        public int $id,
        public int $idActaRecepcion,
        public int $idArticulo,
        public float $cantidadRecibida,
        public string $estadoFisico
    ) {}

    public function toArray(): array
    {
        return [
            'id_acta_recepcion_detalle' => $this->id,
            'id_acta_recepcion' => $this->idActaRecepcion,
            'id_articulo' => $this->idArticulo,
            'cantidad_recibida' => $this->cantidadRecibida,
            'estado_fisico' => $this->estadoFisico
        ];
    }
}
