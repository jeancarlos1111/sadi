<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use PDO;

class UsuarioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'usuario';
    }

    public function paginate(...$args): array
    {
        $search  = $args[0] ?? '';
        $page    = $args[1] ?? 1;
        $perPage = $args[2] ?? 15;

        $db       = $this->getPdo();
        $bindings = [];

        $sql = "
            SELECT 
                U.id_usuario, U.usuario, U.cedula_personal,
                P.nombres, P.apellidos
            FROM usuario AS U
            LEFT JOIN personal AS P ON U.cedula_personal = P.cod_personal AND P.eliminado = false
            WHERE U.eliminado = false
        ";

        if ($search !== '') {
            $sql .= " AND (U.usuario ILIKE :search OR P.nombres ILIKE :search OR P.apellidos ILIKE :search)";
            $bindings['search'] = "%$search%";
        }
        $sql .= " ORDER BY U.usuario ASC";

        $paginator = \App\Database\Paginator::paginateRaw($db, $sql, $bindings, $page, $perPage);

        $results = [];
        foreach ($paginator['data'] as $row) {
            $entity    = $this->mapRow($row);
            $results[] = [
                'entity'          => $entity,
                'nombre_completo' => $row['nombres']
                    ? ($row['nombres'] . ' ' . $row['apellidos'])
                    : 'Usuario de Sistema (Root)',
            ];
        }

        $paginator['data'] = $results;
        return $paginator;
    }

    public function find(int $id): ?Usuario
    {
        $row = $this->query()->where('id_usuario', '=', $id)->where('eliminado', '=', 'false')->first();

        return $row ? $this->mapRow($row) : null;
    }

    public function findByUsername(string $username): ?Usuario
    {
        $row = $this->query()->where('usuario', '=', $username)->where('eliminado', '=', 'false')->first();

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * Valida credenciales. Retorna DTO si es válido, null en caso contrario.
     */
    public function findByCredentials(string $username, string $password): ?Usuario
    {
        $stmt = $this->getPdo()->prepare("
            SELECT id_usuario, usuario, contrasenya, cedula_personal
            FROM usuario
            WHERE usuario = :u AND eliminado = false
            LIMIT 1
        ");
        $stmt->execute(['u' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $hash = $row['contrasenya'];

        $pepper = getenv('PASSWORD_SALT') ?: '';
        $valid = password_verify($password . $pepper, $hash);

        if (!$valid) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * Crea o actualiza un usuario.
     * Si se provee 'password', lo hashea con bcrypt y valida longitud mínima de 8.
     *
     * @throws \InvalidArgumentException si la contraseña tiene menos de 8 caracteres.
     */
    public function save(array $data): bool
    {
        $pdo = $this->getPdo();

        if (isset($data['password'])) {
            if (strlen($data['password']) < 8) {
                throw new \InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
            }
            $pepper = getenv('PASSWORD_SALT') ?: '';
            $data['contrasenya'] = password_hash($data['password'] . $pepper, PASSWORD_BCRYPT);
        }

        if (!empty($data['id'])) {
            $sets    = [];
            $params  = [];

            if (isset($data['usuario'])) {
                $sets[]            = "usuario = :usuario";
                $params['usuario'] = $data['usuario'];
            }
            if (isset($data['contrasenya'])) {
                $sets[]                = "contrasenya = :contrasenya";
                $params['contrasenya'] = $data['contrasenya'];
            }
            if (array_key_exists('cedula_personal', $data)) {
                $sets[]                   = "cedula_personal = :cedula_personal";
                $params['cedula_personal'] = $data['cedula_personal'];
            }

            if (empty($sets)) {
                return true;
            }

            $params['id'] = $data['id'];
            $stmt = $pdo->prepare("UPDATE usuario SET " . implode(', ', $sets) . " WHERE id_usuario = :id");

            return $stmt->execute($params);
        }

        $stmt = $pdo->prepare("
            INSERT INTO usuario (usuario, contrasenya, cedula_personal)
            VALUES (:usuario, :contrasenya, :cedula_personal)
        ");

        return $stmt->execute([
            'usuario'         => $data['usuario'],
            'contrasenya'     => $data['contrasenya'] ?? password_hash('Sadi2026!', PASSWORD_BCRYPT),
            'cedula_personal' => $data['cedula_personal'] ?? null,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_usuario', '=', $id)->update(['eliminado' => 'true']);
    }

    /**
     * @return Rol[]
     */
    public function getRoles(int $idUsuario): array
    {
        $stmt = $this->getPdo()->prepare("
            SELECT r.id_rol, r.nombre, r.descripcion
            FROM rol r
            JOIN usuario_rol ur ON ur.id_rol = r.id_rol
            WHERE ur.id_usuario = ? AND r.eliminado = false
            ORDER BY r.nombre
        ");
        $stmt->execute([$idUsuario]);

        return array_map(
            fn ($row) => Rol::fromArray([
                'id'          => (int)$row['id_rol'],
                'nombre'      => $row['nombre'],
                'descripcion' => $row['descripcion'] ?? null,
            ]),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Reemplaza completamente los roles de un usuario.
     */
    public function syncRoles(int $idUsuario, array $idsRoles): void
    {
        $pdo = $this->getPdo();

        $pdo->prepare("DELETE FROM usuario_rol WHERE id_usuario = ?")->execute([$idUsuario]);

        if (!empty($idsRoles)) {
            $stmt = $pdo->prepare("INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (?, ?)");
            foreach ($idsRoles as $idRol) {
                $stmt->execute([$idUsuario, (int)$idRol]);
            }
        }
    }

    /**
     * Devuelve todos los permisos del usuario derivados de sus roles.
     * Formato del array resultante: ['presupuesto.gastos.ver' => true, ...]
     *
     * @return array<string, true>
     */
    public function getPermisos(int $idUsuario): array
    {
        $stmt = $this->getPdo()->prepare("
            SELECT DISTINCT p.modulo, p.seccion, p.accion
            FROM permiso p
            JOIN rol_permiso rp ON rp.id_permiso = p.id_permiso
            JOIN usuario_rol ur ON ur.id_rol = rp.id_rol
            WHERE ur.id_usuario = ?
        ");
        $stmt->execute([$idUsuario]);

        $permisos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $clave = "{$row['modulo']}.{$row['seccion']}.{$row['accion']}";
            $permisos[$clave] = true;
        }

        return $permisos;
    }

    private function mapRow(array $row): Usuario
    {
        return new Usuario(
            (int)$row['id_usuario'],
            $row['usuario'],
            isset($row['cedula_personal']) ? (int)$row['cedula_personal'] : null
        );
    }
}

