<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Usuario;

class UsuarioDTO
{
    public function __construct(
        public int $id,
        public string $usuario,
        public ?int $cedulaPersonal
    ) {
    }

    public static function fromModel(Usuario $model): self
    {
        return new self(
            $model->id,
            $model->usuario,
            $model->cedulaPersonal
        );
    }
}
