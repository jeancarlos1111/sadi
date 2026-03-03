<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Usuario;
use PDO;

class UsuarioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'usuario';
    }

    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                U.id_usuario, U.usuario, U.cedula_personal,
                P.nombres, P.apellidos
            FROM usuario AS U
            LEFT JOIN personal AS P ON U.cedula_personal = P.cod_personal AND P.eliminado = false
            WHERE U.eliminado = false
        ";

        if ($search !== '') {
            $sql .= " AND (U.usuario LIKE :search OR P.nombres LIKE :search OR P.apellidos LIKE :search)";
        }
        $sql .= " ORDER BY U.usuario ASC";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $entity = new Usuario(
                (int)$row['id_usuario'],
                $row['usuario'],
                $row['cedula_personal'] !== null ? (int)$row['cedula_personal'] : null
            );
            $results[] = [
                'entity' => $entity,
                'nombre_completo' => $row['nombres'] ? ($row['nombres'] . ' ' . $row['apellidos']) : 'Usuario de Sistema (Root)',
            ];
        }

        return $results;
    }

    public function find(int $id): ?Usuario
    {
        $row = $this->query()->where('id_usuario', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return new Usuario(
            (int)$row['id_usuario'],
            $row['usuario'],
            $row['cedula_personal'] !== null ? (int)$row['cedula_personal'] : null
        );
    }

    public function findByUsername(string $username): ?Usuario
    {
        $row = $this->query()->where('usuario', '=', $username)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return new Usuario(
            (int)$row['id_usuario'],
            $row['usuario'],
            $row['cedula_personal'] !== null ? (int)$row['cedula_personal'] : null
        );
    }
}
