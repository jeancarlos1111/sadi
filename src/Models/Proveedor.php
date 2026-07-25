<?php

declare(strict_types=1);

namespace App\Models;

readonly class Proveedor
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $rif,
        public string $compania,
        public int $idTipoOrganizacion,
        public ?string $direccion = null,
        public ?string $telefono = null,
        public ?string $nit = null,
        public ?int $idCodigoContable = null,
        public ?string $numeroRnc = null,
        public ?string $fechaVencimientoRnc = null,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'rif' => $this->rif,
            'compania' => $this->compania,
            'idTipoOrganizacion' => $this->idTipoOrganizacion,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'nit' => $this->nit,
            'idCodigoContable' => $this->idCodigoContable,
            'numeroRnc' => $this->numeroRnc,
            'fechaVencimientoRnc' => $this->fechaVencimientoRnc,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['rif'] ?? null,
            $data['compania'] ?? null,
            $data['idTipoOrganizacion'] ?? null,
            $data['direccion'] ?? null,
            $data['telefono'] ?? null,
            $data['nit'] ?? null,
            $data['idCodigoContable'] ?? null,
            $data['numeroRnc'] ?? null,
            $data['fechaVencimientoRnc'] ?? null,
            $data['id'] ?? null,
        );
    }
}
