<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Permiso;
use App\Models\Rol;
use PDO;

class RolRepository extends Repository
{
    protected function getTable(): string
    {
        return 'rol';
    }

    /**
     * @return Rol[]
     */
    public function all(): array
    {
        $stmt = $this->getPdo()->query("
            SELECT id_rol, nombre, descripcion
            FROM rol
            WHERE eliminado = false
            ORDER BY nombre
        ");

        return array_map(
            fn ($row) => $this->mapRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function paginate(...$args): array
    {
        $search  = $args[0] ?? '';
        $page    = $args[1] ?? 1;
        $perPage = $args[2] ?? 15;

        $db       = $this->getPdo();
        $bindings = [];

        $sql = "
            SELECT id_rol, nombre, descripcion
            FROM rol
            WHERE eliminado = false
        ";

        if ($search !== '') {
            $sql .= " AND (nombre ILIKE :search OR descripcion ILIKE :search)";
            $bindings['search'] = "%$search%";
        }
        $sql .= " ORDER BY nombre ASC";

        $paginator = \App\Database\Paginator::paginateRaw($db, $sql, $bindings, $page, $perPage);

        $results = [];
        foreach ($paginator['data'] as $row) {
            $results[] = clone $this->mapRow($row);
        }

        $paginator['data'] = $results;
        return $paginator;
    }

    public function find(int $id): ?Rol
    {
        $stmt = $this->getPdo()->prepare("
            SELECT id_rol, nombre, descripcion FROM rol WHERE id_rol = ? AND eliminado = false
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    public function save(Rol $rol): bool
    {
        if ($rol->id) {
            $stmt = $this->getPdo()->prepare("
                UPDATE rol SET nombre = :nombre, descripcion = :descripcion WHERE id_rol = :id
            ");
            return $stmt->execute([
                'nombre'      => $rol->nombre,
                'descripcion' => $rol->descripcion,
                'id'          => $rol->id,
            ]);
        }

        $stmt = $this->getPdo()->prepare("
            INSERT INTO rol (nombre, descripcion) VALUES (:nombre, :descripcion)
        ");
        return $stmt->execute([
            'nombre'      => $rol->nombre,
            'descripcion' => $rol->descripcion,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->getPdo()->prepare("UPDATE rol SET eliminado = true WHERE id_rol = ?");
        return $stmt->execute([$id]);
    }

    /**
     * @return Permiso[]
     */
    public function getPermisos(int $idRol): array
    {
        $stmt = $this->getPdo()->prepare("
            SELECT p.id_permiso, p.modulo, p.seccion, p.accion, p.descripcion
            FROM permiso p
            JOIN rol_permiso rp ON rp.id_permiso = p.id_permiso
            WHERE rp.id_rol = ?
            ORDER BY p.modulo, p.seccion, p.accion
        ");
        $stmt->execute([$idRol]);

        return array_map(
            fn ($row) => new Permiso(
                (int)$row['id_permiso'],
                $row['modulo'],
                $row['seccion'],
                $row['accion'],
                $row['descripcion'] ?? null
            ),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Reemplaza completamente los permisos de un rol.
     */
    public function syncPermisos(int $idRol, array $idsPermisos): void
    {
        $pdo = $this->getPdo();

        $pdo->prepare("DELETE FROM rol_permiso WHERE id_rol = ?")->execute([$idRol]);

        if (!empty($idsPermisos)) {
            $stmt = $pdo->prepare("INSERT INTO rol_permiso (id_rol, id_permiso) VALUES (?, ?)");
            foreach ($idsPermisos as $idPermiso) {
                $stmt->execute([$idRol, (int)$idPermiso]);
            }
        }
    }

    private function mapRow(array $row): Rol
    {
        return Rol::fromArray([
            'id'          => (int)$row['id_rol'],
            'nombre'      => $row['nombre'],
            'descripcion' => $row['descripcion'] ?? null,
        ]);
    }
}
