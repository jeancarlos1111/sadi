<?php

declare(strict_types=1);

namespace App\Models;

readonly class ProcesoContratacion
{
    public const CONSULTA_DE_PRECIOS = 'CONSULTA_DE_PRECIOS';
    public const CONCURSO_CERRADO = 'CONCURSO_CERRADO';
    public const CONCURSO_ABIERTO = 'CONCURSO_ABIERTO';
    public const CONTRATACION_DIRECTA = 'CONTRATACION_DIRECTA';

    public function __construct(
        public int $id,
        public string $numeroExpediente,
        public string $descripcion,
        public string $modalidad,
        public float $montoEstimado,
        public ?int $idOrdenCompra = null,
        public ?string $justificacionLegal = null,
        public bool $crsAplicable = false,
        public ?string $numeroCrs = null,
        public string $estatus = 'ABIERTO',
        public ?string $fechaApertura = null,
        public ?string $fechaCierre = null,
        public ?int $idUsuarioCreador = null,
        public bool $eliminado = false
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numeroExpediente' => $this->numeroExpediente,
            'descripcion' => $this->descripcion,
            'modalidad' => $this->modalidad,
            'montoEstimado' => $this->montoEstimado,
            'idOrdenCompra' => $this->idOrdenCompra,
            'justificacionLegal' => $this->justificacionLegal,
            'crsAplicable' => $this->crsAplicable,
            'numeroCrs' => $this->numeroCrs,
            'estatus' => $this->estatus,
            'fechaApertura' => $this->fechaApertura,
            'fechaCierre' => $this->fechaCierre,
            'idUsuarioCreador' => $this->idUsuarioCreador,
            'eliminado' => $this->eliminado,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? 0,
            $data['numeroExpediente'] ?? '',
            $data['descripcion'] ?? '',
            $data['modalidad'] ?? self::CONSULTA_DE_PRECIOS,
            (float)($data['montoEstimado'] ?? 0),
            $data['idOrdenCompra'] ?? null,
            $data['justificacionLegal'] ?? null,
            (bool)($data['crsAplicable'] ?? false),
            $data['numeroCrs'] ?? null,
            $data['estatus'] ?? 'ABIERTO',
            $data['fechaApertura'] ?? null,
            $data['fechaCierre'] ?? null,
            $data['idUsuarioCreador'] ?? null,
            (bool)($data['eliminado'] ?? false)
        );
    }
}
