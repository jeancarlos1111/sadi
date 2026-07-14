<?php

declare(strict_types=1);

namespace App\Models;

readonly class RequisicionBienes
{
    // Property Hooks
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $fecha,
        public string $concepto,
        public int $idEstructuraPresupuestaria,
        public array $articulos = [], // Array of associative arrays: ['id_articulo' => X, 'cantidad' => Y]
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha,
            'concepto' => $this->concepto,
            'idEstructuraPresupuestaria' => $this->idEstructuraPresupuestaria,
            'articulos' => $this->articulos,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['fecha'] ?? null,
            $data['concepto'] ?? null,
            $data['idEstructuraPresupuestaria'] ?? null,
            $data['articulos'] ?? null,
            $data['id'] ?? null,
        );
    }
}
