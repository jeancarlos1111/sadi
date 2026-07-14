<?php

declare(strict_types=1);

namespace App\Models;

readonly class Servicio
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string  $denominacion,
        public ?string $descripcion,
        public int     $idTipoServicio,
        public bool    $aplicarIva = false,
        public ?int    $idCodigoPlanUnico = null,
        ?int           $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'denominacion' => $this->denominacion,
            'descripcion' => $this->descripcion,
            'idTipoServicio' => $this->idTipoServicio,
            'aplicarIva' => $this->aplicarIva,
            'idCodigoPlanUnico' => $this->idCodigoPlanUnico,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['denominacion'] ?? null,
            $data['descripcion'] ?? null,
            $data['idTipoServicio'] ?? null,
            $data['aplicarIva'] ?? null,
            $data['idCodigoPlanUnico'] ?? null,
            $data['id'] ?? null,
        );
    }
}
