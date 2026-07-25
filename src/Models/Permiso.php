<?php

declare(strict_types=1);

namespace App\Models;

readonly class Permiso
{
    public function __construct(
        public int $id,
        public string $modulo,
        public string $seccion,
        public string $accion,
        public ?string $descripcion = null,
    ) {
    }

    /**
     * Clave canónica del permiso: "modulo.seccion.accion"
     */
    public function clave(): string
    {
        return "{$this->modulo}.{$this->seccion}.{$this->accion}";
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'modulo'      => $this->modulo,
            'seccion'     => $this->seccion,
            'accion'      => $this->accion,
            'descripcion' => $this->descripcion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['id'] ?? 0),
            $data['modulo']      ?? '',
            $data['seccion']     ?? '*',
            $data['accion']      ?? '',
            $data['descripcion'] ?? null,
        );
    }
}
