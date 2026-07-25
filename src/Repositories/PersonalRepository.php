<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Personal;
use PDO;

class PersonalRepository extends Repository
{
    protected function getTable(): string
    {
        return 'personal';
    }

    public function countAll(string $search = ''): int
    {
        $db = $this->getPdo();
        $sql = "SELECT COUNT(*) FROM personal AS P WHERE P.eliminado = false";
        if ($search !== '') {
            $sql .= " AND (P.cedula ILIKE :search OR P.nombres ILIKE :search OR P.apellidos ILIKE :search)";
        }
        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function all(string $search = '', ?int $limit = null, ?int $offset = null): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                P.cod_personal, P.cedula, P.nombres, P.apellidos, P.fecha_nacimiento,
                F.cod_ficha, F.ingreso, F.sueldo_basico, F.banco, C.nombre AS cargo, N.denom AS nomina_nombre
            FROM personal AS P
            LEFT JOIN ficha AS F ON P.cod_personal = F.personal_cod_personal AND F.eliminado = false
            LEFT JOIN cargo AS C ON F.cargo_cod_cargo = C.cod_cargo
            LEFT JOIN nomina AS N ON F.nomina_cod_nomina = N.cod_nomina
            WHERE P.eliminado = false 
        ";

        if ($search !== '') {
            $sql .= " AND (P.cedula ILIKE :search OR P.nombres ILIKE :search OR P.apellidos ILIKE :search)";
        }
        $sql .= " ORDER BY P.cedula ASC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        if ($offset !== null) {
            $sql .= " OFFSET " . (int)$offset;
        }

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = [
                'cod_personal'  => $row['cod_personal'],
                'cedula'        => $row['cedula'],
                'nombres'       => $row['nombres'],
                'apellidos'     => $row['apellidos'],
                'fecha_nacimiento' => $row['fecha_nacimiento'],
                'cod_ficha'     => $row['cod_ficha'] ?? 0,
                'ingreso'       => $row['ingreso'] ?? '',
                'sueldo_basico' => (float)($row['sueldo_basico'] ?? 0),
                'banco'         => $row['banco'] ?? null,
                'cargo'         => $row['cargo'] ?? 'Sin asignar',
                'nomina'        => $row['nomina_nombre'] ?? 'Sin asignar',
            ];
        }

        return $results;
    }

    private function mapRowToEntity(array $row): Personal
    {
        return new Personal(
            (int)$row['cod_personal'],
            $row['cedula'],
            $row['nombres'],
            $row['apellidos'],
            $row['fecha_nacimiento'] ?? '',
            $row['rif'] ?? null,
            $row['telefono'] ?? null,
            $row['direccion'] ?? null,
            $row['correo'] ?? null,
            $row['estado_civil'] ?? 'SOLTERO',
            isset($row['cargas_familiares']) ? (int)$row['cargas_familiares'] : 0,
            $row['nivel_instruccion'] ?? null
        );
    }

    public function find(int $id): ?Personal
    {
        $row = $this->query()->where('cod_personal', '=', $id)->where('eliminado', '=', 'false')->first();
        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function save(Personal $item): bool
    {
        $data = [
            'cedula' => $item->cedula,
            'nombres' => $item->nombres,
            'apellidos' => $item->apellidos,
            'fecha_nacimiento' => $item->fechaNacimiento,
            'rif' => $item->rif,
            'telefono' => $item->telefono,
            'direccion' => $item->direccion,
            'correo' => $item->correo,
            'estado_civil' => $item->estadoCivil,
            'cargas_familiares' => $item->cargasFamiliares,
            'nivel_instruccion' => $item->nivelInstruccion,
        ];

        if ($item->codPersonal > 0) {
            return $this->query()->where('cod_personal', '=', $item->codPersonal)->update($data);
        }

        $id = $this->query()->insert($data);
        if ($id) {
            return true;
        }

        return false;
    }
}
