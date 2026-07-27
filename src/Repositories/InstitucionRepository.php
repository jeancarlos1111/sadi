<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use App\Models\Institucion;
use PDO;

class InstitucionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function getConfig(): Institucion
    {
        $stmt = $this->pdo->prepare("SELECT * FROM institucion WHERE id_institucion = 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return Institucion::fromArray([
                'id_institucion' => 1,
                'nombre' => 'Configurar Institución',
                'rif' => '',
                'direccion' => ''
            ]);
        }

        return Institucion::fromArray($row);
    }

    public function saveConfig(Institucion $institucion): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO institucion (
                id_institucion, nombre, rif, direccion, telefono, correo, 
                maxima_autoridad, cargo_autoridad, base_legal, codigo_onapre, logo_path, marca_agua_activa
            ) VALUES (
                :id_institucion, :nombre, :rif, :direccion, :telefono, :correo, 
                :maxima_autoridad, :cargo_autoridad, :base_legal, :codigo_onapre, :logo_path, :marca_agua_activa
            )
            ON CONFLICT (id_institucion) DO UPDATE SET
                nombre = EXCLUDED.nombre,
                rif = EXCLUDED.rif,
                direccion = EXCLUDED.direccion,
                telefono = EXCLUDED.telefono,
                correo = EXCLUDED.correo,
                maxima_autoridad = EXCLUDED.maxima_autoridad,
                cargo_autoridad = EXCLUDED.cargo_autoridad,
                base_legal = EXCLUDED.base_legal,
                codigo_onapre = EXCLUDED.codigo_onapre,
                logo_path = EXCLUDED.logo_path,
                marca_agua_activa = EXCLUDED.marca_agua_activa
        ");

        $stmt->execute([
            'id_institucion' => 1, // Singleton
            'nombre' => $institucion->nombre,
            'rif' => $institucion->rif,
            'direccion' => $institucion->direccion,
            'telefono' => $institucion->telefono,
            'correo' => $institucion->correo,
            'maxima_autoridad' => $institucion->maxima_autoridad,
            'cargo_autoridad' => $institucion->cargo_autoridad,
            'base_legal' => $institucion->base_legal,
            'codigo_onapre' => $institucion->codigo_onapre,
            'logo_path' => $institucion->logo_path,
            'marca_agua_activa' => $institucion->marca_agua_activa ? 1 : 0
        ]);
    }
}
