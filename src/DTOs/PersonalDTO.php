<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Personal;

class PersonalDTO
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

    public static function fromModel(Personal $model): self
    {
        return new self(
            $model->codPersonal,
            $model->cedula,
            $model->nombres,
            $model->apellidos,
            $model->fechaNacimiento,
            $model->rif,
            $model->telefono,
            $model->direccion,
            $model->correo,
            $model->estadoCivil,
            $model->cargasFamiliares,
            $model->nivelInstruccion
        );
    }
}
