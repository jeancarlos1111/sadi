<?php

declare(strict_types=1);

namespace App\Models;

readonly class Personal
{
    public function __construct(
        public int $codPersonal,
        public string $cedula,
        public string $nombres,
        public string $apellidos,
        public string $fechaNacimiento
    ) {
    }

    public function toArray(): array
    {
        return [
            'codPersonal' => $this->codPersonal,
            'cedula' => $this->cedula,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'fechaNacimiento' => $this->fechaNacimiento,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['codPersonal'] ?? null,
            $data['cedula'] ?? null,
            $data['nombres'] ?? null,
            $data['apellidos'] ?? null,
            $data['fechaNacimiento'] ?? null,
        );
    }
}
