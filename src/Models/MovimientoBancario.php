<?php

declare(strict_types=1);

namespace App\Models;

readonly class MovimientoBancario
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public int $idCuenta,
        public int $idTipoOperacion,
        public float $monto,
        public string $fecha,
        public string $referencia,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idCuenta' => $this->idCuenta,
            'idTipoOperacion' => $this->idTipoOperacion,
            'monto' => $this->monto,
            'fecha' => $this->fecha,
            'referencia' => $this->referencia,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['idCuenta'] ?? null,
            $data['idTipoOperacion'] ?? null,
            $data['monto'] ?? null,
            $data['fecha'] ?? null,
            $data['referencia'] ?? null,
            $data['id'] ?? null,
        );
    }
}
