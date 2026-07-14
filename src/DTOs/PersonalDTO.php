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
        public string $fechaNacimiento
    ) {
    }

    public static function fromModel(Personal $model): self
    {
        return new self(
            $model->codPersonal,
            $model->cedula,
            $model->nombres,
            $model->apellidos,
            $model->fechaNacimiento
        );
    }
}
