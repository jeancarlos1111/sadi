<?php

declare(strict_types=1);

namespace App\Models;

readonly class AsignacionBien
{
    public function __construct(
        public int $id,
        public string $numeroAsignacion,
        public int $idArticulo,
        public string $cedulaResponsable,
        public int $idUnidadAdministrativa,
        public string $fechaAsignacion,
        public string $estadoAsignacion = 'ACTIVA',
        public bool $eliminado = false
    ) {}

    public function toArray(): array
    {
        return [
            'id_asignacion' => $this->id,
            'numero_asignacion' => $this->numeroAsignacion,
            'id_articulo' => $this->idArticulo,
            'cedula_responsable' => $this->cedulaResponsable,
            'id_unidad_administrativa' => $this->idUnidadAdministrativa,
            'fecha_asignacion' => $this->fechaAsignacion,
            'estado_asignacion' => $this->estadoAsignacion,
            'eliminado' => $this->eliminado
        ];
    }
}
