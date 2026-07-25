<?php

declare(strict_types=1);

namespace App\Models;

class DespachoAlmacen
{
    public function __construct(
        public readonly string $numeroDespacho,
        public readonly string $fechaDespacho,
        public readonly int $idUnidadAdministrativa,
        public readonly string $solicitante,
        public readonly int $idUsuarioDespacha,
        public readonly ?string $observaciones,
        public readonly string $estado = 'DESPACHADO',
        public readonly ?int $idDespachoAlmacen = null,
        public readonly array $detalles = [] // [id_articulo, cantidad_despachada]
    ) {}

    public function toArray(): array
    {
        return [
            'id_despacho_almacen' => $this->idDespachoAlmacen,
            'numero_despacho' => $this->numeroDespacho,
            'fecha_despacho' => $this->fechaDespacho,
            'id_unidad_administrativa' => $this->idUnidadAdministrativa,
            'solicitante' => $this->solicitante,
            'id_usuario_despacha' => $this->idUsuarioDespacha,
            'observaciones' => $this->observaciones,
            'estado' => $this->estado,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['numero_despacho'],
            $data['fecha_despacho'],
            (int)$data['id_unidad_administrativa'],
            $data['solicitante'],
            (int)$data['id_usuario_despacha'],
            $data['observaciones'] ?? null,
            $data['estado'] ?? 'DESPACHADO',
            isset($data['id_despacho_almacen']) ? (int)$data['id_despacho_almacen'] : null
        );
    }
}
