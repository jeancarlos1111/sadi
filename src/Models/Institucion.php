<?php

declare(strict_types=1);

namespace App\Models;

readonly class Institucion
{
    public function __construct(
        public int $id_institucion,
        public string $nombre,
        public string $rif,
        public string $direccion,
        public ?string $telefono,
        public ?string $correo,
        public ?string $maxima_autoridad,
        public ?string $cargo_autoridad,
        public ?string $base_legal,
        public ?string $codigo_onapre,
        public ?string $logo_path,
        public bool $marca_agua_activa = true,
        public ?string $creado_en = null,
        public ?string $actualizado_en = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['id_institucion'] ?? 1),
            $data['nombre'] ?? '',
            $data['rif'] ?? '',
            $data['direccion'] ?? '',
            $data['telefono'] ?? null,
            $data['correo'] ?? null,
            $data['maxima_autoridad'] ?? null,
            $data['cargo_autoridad'] ?? null,
            $data['base_legal'] ?? null,
            $data['codigo_onapre'] ?? null,
            $data['logo_path'] ?? null,
            isset($data['marca_agua_activa']) ? (bool)$data['marca_agua_activa'] : true,
            $data['creado_en'] ?? null,
            $data['actualizado_en'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id_institucion' => $this->id_institucion,
            'nombre' => $this->nombre,
            'rif' => $this->rif,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'maxima_autoridad' => $this->maxima_autoridad,
            'cargo_autoridad' => $this->cargo_autoridad,
            'base_legal' => $this->base_legal,
            'codigo_onapre' => $this->codigo_onapre,
            'logo_path' => $this->logo_path,
            'marca_agua_activa' => $this->marca_agua_activa,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en
        ];
    }
}
