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
        public string $fechaNacimiento,
        public ?string $rif = null,
        public ?string $telefono = null,
        public ?string $direccion = null,
        public ?string $correo = null,
        public ?string $estadoCivil = 'SOLTERO',
        public ?int $cargasFamiliares = 0,
        public ?string $nivelInstruccion = null
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
            'rif' => $this->rif,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'correo' => $this->correo,
            'estadoCivil' => $this->estadoCivil,
            'cargasFamiliares' => $this->cargasFamiliares,
            'nivelInstruccion' => $this->nivelInstruccion,
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
            $data['rif'] ?? null,
            $data['telefono'] ?? null,
            $data['direccion'] ?? null,
            $data['correo'] ?? null,
            $data['estadoCivil'] ?? 'SOLTERO',
            isset($data['cargasFamiliares']) ? (int)$data['cargasFamiliares'] : 0,
            $data['nivelInstruccion'] ?? null
        );
    }
}
