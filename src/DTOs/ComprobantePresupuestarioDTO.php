<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\ComprobantePresupuestario;

class ComprobantePresupuestarioDTO
{
    public function __construct(
        public ?int $id_comprobante,
        public string $acronimo_c,
        public string $numero_c,
        public string $fecha_c,
        public string $denominacion_c,
        public ?string $referencia_c = null,
        public ?string $beneficiario_cedula = null,
        public string $estado = 'APROBADO'
    ) {
    }

    public static function fromModel(ComprobantePresupuestario $model): self
    {
        return new self(
            $model->id_comprobante,
            $model->acronimo_c,
            $model->numero_c,
            $model->fecha_c,
            $model->denominacion_c,
            $model->referencia_c,
            $model->beneficiario_cedula,
            $model->estado
        );
    }
}
