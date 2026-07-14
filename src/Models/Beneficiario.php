<?php

declare(strict_types=1);

namespace App\Models;

readonly class Beneficiario
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string  $cedula,
        public string  $nombres,
        public string  $apellidos,
        public string  $direccion,
        public string  $telefono,
        public ?string $email = null,
        public ?int    $idCodigoContable = null,
        ?int           $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cedula' => $this->cedula,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'idCodigoContable' => $this->idCodigoContable,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['cedula'] ?? null,
            $data['nombres'] ?? null,
            $data['apellidos'] ?? null,
            $data['direccion'] ?? null,
            $data['telefono'] ?? null,
            $data['email'] ?? null,
            $data['idCodigoContable'] ?? null,
            $data['id'] ?? null,
        );
    }
}
