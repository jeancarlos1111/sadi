<?php

declare(strict_types=1);

namespace App\Models;

class TomaInventarioDetalle
{
    public function __construct(
        public int $idToma,
        public string $tipo,
        public int $idArticulo,
        public int $cantidadSistema = 0,
        public int $cantidadFisica = 0,
        public int $diferencia = 0,
        public ?string $justificacion = null,
        public ?int $idDetalle = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_detalle' => $this->idDetalle,
            'id_toma' => $this->idToma,
            'tipo' => $this->tipo,
            'id_articulo' => $this->idArticulo,
            'cantidad_sistema' => $this->cantidadSistema,
            'cantidad_fisica' => $this->cantidadFisica,
            'diferencia' => $this->diferencia,
            'justificacion' => $this->justificacion
        ];
    }
}
