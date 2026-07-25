<?php

declare(strict_types=1);

namespace App\Models;

class InventarioBien
{
    public function __construct(
        public int $idArticulo,
        public int $idProveedor,
        public string $fechaCompraIb,
        public int $idOrdenDeCompra,
        public float $costoIb,
        public int $idEstadoBienes,
        public int $idUbicacionArticulo,
        public string $acronimoIdIb,
        public bool $revisado = false,
        public int $vidaUtilMeses = 0,
        public float $valorResidual = 0.0,
        public ?int $idInventarioBienes = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id_inventario_bienes' => $this->idInventarioBienes,
            'id_articulo' => $this->idArticulo,
            'id_proveedor' => $this->idProveedor,
            'fecha_compra_ib' => $this->fechaCompraIb,
            'id_orden_de_compra' => $this->idOrdenDeCompra,
            'costo_ib' => $this->costoIb,
            'id_estado_bienes' => $this->idEstadoBienes,
            'id_ubicacion_articulo' => $this->idUbicacionArticulo,
            'acronimo_id_ib' => $this->acronimoIdIb,
            'revisado' => $this->revisado,
            'vida_util_meses' => $this->vidaUtilMeses,
            'valor_residual' => $this->valorResidual,
        ];
    }
}
