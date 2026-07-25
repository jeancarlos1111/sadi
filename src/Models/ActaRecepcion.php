<?php

declare(strict_types=1);

namespace App\Models;

readonly class ActaRecepcion
{
    public function __construct(
        public int $id,
        public string $numeroActa,
        public string $fechaRecepcion,
        public int $idOrdenDeCompra,
        public int $idUsuarioReceptor,
        public bool $conformidad,
        public ?string $observaciones = null,
        public bool $eliminado = false
    ) {}

    public function toArray(): array
    {
        return [
            'id_acta_recepcion' => $this->id,
            'numero_acta' => $this->numeroActa,
            'fecha_recepcion' => $this->fechaRecepcion,
            'id_orden_de_compra' => $this->idOrdenDeCompra,
            'id_usuario_receptor' => $this->idUsuarioReceptor,
            'conformidad' => $this->conformidad,
            'observaciones' => $this->observaciones,
            'eliminado' => $this->eliminado
        ];
    }
}
