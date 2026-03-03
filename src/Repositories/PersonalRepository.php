<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Personal;

class PersonalRepository extends Repository
{
    protected function getTable(): string
    {
        return 'personal';
    }

    /**
     * @return array
     */
    public function all(string $search = ''): array
    {
        // En este caso como es un join complejo, usamos getPdo()
        $db = $this->getPdo();
        $sql = "
            SELECT 
                P.cod_personal, P.cedula, P.nombres, P.apellidos, P.fecha_nacimiento,
                F.sueldo_basico, C.nombre AS cargo, N.denom AS nomina_nombre
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

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $personal = clone $this->mapRowToEntity($row);
            $results[] = [
                'entity' => $personal,
                'cargo'  => $row['cargo'] ?? 'Sin asignar',
                'nomina' => $row['nomina_nombre'] ?? 'Sin asignar',
                'sueldo' => (float)($row['sueldo_basico'] ?? 0),
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
            $row['fecha_nacimiento'] ?? ''
        );
    }
}
