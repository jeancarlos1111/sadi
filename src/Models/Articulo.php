<?php

declare(strict_types=1);

namespace App\Models;

readonly class Articulo
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $denominacion,
        public string $observacion,
        public int $idTipoDeArticulo,
        public int $idUnidadesDeMedida,
        public ?int $idCodigoPlanUnico,
        public bool $aplicarIva,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'denominacion' => $this->denominacion,
            'observacion' => $this->observacion,
            'idTipoDeArticulo' => $this->idTipoDeArticulo,
            'idUnidadesDeMedida' => $this->idUnidadesDeMedida,
            'idCodigoPlanUnico' => $this->idCodigoPlanUnico,
            'aplicarIva' => $this->aplicarIva,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['denominacion'] ?? null,
            $data['observacion'] ?? null,
            $data['idTipoDeArticulo'] ?? null,
            $data['idUnidadesDeMedida'] ?? null,
            $data['idCodigoPlanUnico'] ?? null,
            $data['aplicarIva'] ?? null,
            $data['id'] ?? null,
        );
    }
}
