<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Beneficiario;

class BeneficiarioDTO
{
    public function __construct(
        public ?int $id,
        public string $cedula,
        public string $nombres,
        public string $apellidos,
        public string $direccion,
        public string $telefono,
        public ?string $email = null,
        public ?int $idCodigoContable = null
    ) {
    }

    public static function fromModel(Beneficiario $model): self
    {
        return new self(
            $model->id,
            $model->cedula,
            $model->nombres,
            $model->apellidos,
            $model->direccion,
            $model->telefono,
            $model->email,
            $model->idCodigoContable
        );
    }
}
