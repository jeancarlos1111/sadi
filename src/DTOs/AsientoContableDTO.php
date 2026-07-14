<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\AsientoContable;

class AsientoContableDTO
{
    public function __construct(
        public string $numeroComprobante,
        public string $fecha,
        public string $concepto,
        public float $totalDebe,
        public float $totalHaber,
        public ?int $id = null
    ) {
    }

    public static function fromModel(AsientoContable $model): self
    {
        return new self(
            $model->numeroComprobante,
            $model->fecha,
            $model->concepto,
            $model->totalDebe,
            $model->totalHaber,
            $model->id
        );
    }
}
