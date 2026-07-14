<?php

declare(strict_types=1);

namespace App\Models;

readonly class ComprobantePresupuestario
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

    public function toArray(): array
    {
        return [
            'id_comprobante' => $this->id_comprobante,
            'acronimo_c' => $this->acronimo_c,
            'numero_c' => $this->numero_c,
            'fecha_c' => $this->fecha_c,
            'denominacion_c' => $this->denominacion_c,
            'referencia_c' => $this->referencia_c,
            'beneficiario_cedula' => $this->beneficiario_cedula,
            'estado' => $this->estado,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['acronimo_c'] ?? null,
            $data['numero_c'] ?? null,
            $data['fecha_c'] ?? null,
            $data['denominacion_c'] ?? null,
            $data['referencia_c'] ?? null,
            $data['beneficiario_cedula'] ?? null,
            $data['estado'] ?? null,
            $data['id_comprobante'] ?? null,
        );
    }
}
