<?php

declare(strict_types=1);

namespace App\Models;

readonly class AjustePresupuestario
{
    /* private(set) */ public ?int $id;

    public function __construct(
        public string $tipoAjuste, // TRASPASO, CREDITO_ADICIONAL, REDUCCION
        public string $fecha,
        public string $concepto,
        public float $montoTotal,
        public string $estado, // PENDIENTE, APROBADO
        ?int $id = null
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tipoAjuste' => $this->tipoAjuste,
            'fecha' => $this->fecha,
            'concepto' => $this->concepto,
            'montoTotal' => $this->montoTotal,
            'estado' => $this->estado,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['tipoAjuste'] ?? null,
            $data['fecha'] ?? null,
            $data['concepto'] ?? null,
            $data['montoTotal'] ?? null,
            $data['estado'] ?? null,
            $data['id'] ?? null,
        );
    }
}
