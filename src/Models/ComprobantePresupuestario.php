<?php

namespace App\Models;

class ComprobantePresupuestario
{
    public ?int $id_comprobante;

    public function __construct(
        public string $acronimo_c,
        public string $numero_c,
        public string $fecha_c,
        public string $denominacion_c,
        public ?string $referencia_c = null,
        public ?string $beneficiario_cedula = null,
        public string $estado = 'APROBADO',
        ?int $id_comprobante = null
    ) {
        $this->id_comprobante = $id_comprobante;
    }
}
