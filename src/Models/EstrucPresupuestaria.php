<?php

declare(strict_types=1);

namespace App\Models;

readonly class EstrucPresupuestaria
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $descripcion,
        public int $idAccionesCentralizadas = 0,
        public int $idAccionEspecifica = 0,
        public int $idOtrasAcciones = 0,
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'descripcion' => $this->descripcion,
            'idAccionesCentralizadas' => $this->idAccionesCentralizadas,
            'idAccionEspecifica' => $this->idAccionEspecifica,
            'idOtrasAcciones' => $this->idOtrasAcciones,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['descripcion'] ?? null,
            $data['idAccionesCentralizadas'] ?? null,
            $data['idAccionEspecifica'] ?? null,
            $data['idOtrasAcciones'] ?? null,
            $data['id'] ?? null,
        );
    }
}
