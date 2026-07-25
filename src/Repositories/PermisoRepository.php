<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Permiso;
use PDO;

class PermisoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'permiso';
    }

    /**
     * @return Permiso[]
     */
    public function all(): array
    {
        $stmt = $this->getPdo()->query("
            SELECT id_permiso, modulo, seccion, accion, descripcion
            FROM permiso
            ORDER BY modulo, seccion, accion
        ");

        return array_map(
            fn ($row) => $this->mapRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Devuelve los permisos agrupados: ['presupuesto' => ['gastos' => [Permiso, ...], ...], ...]
     *
     * @return array<string, array<string, Permiso[]>>
     */
    public function allGroupedByModuloSeccion(): array
    {
        $permisos = $this->all();
        $grouped  = [];

        foreach ($permisos as $permiso) {
            $grouped[$permiso->modulo][$permiso->seccion][] = $permiso;
        }

        return $grouped;
    }

    public function find(int $id): ?Permiso
    {
        $row = $this->query()->where('id_permiso', '=', $id)->first();

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * @return Permiso[]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->getPdo()->prepare("
            SELECT id_permiso, modulo, seccion, accion, descripcion
            FROM permiso
            WHERE id_permiso IN ({$placeholders})
        ");
        $stmt->execute($ids);

        return array_map(
            fn ($row) => $this->mapRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    private function mapRow(array $row): Permiso
    {
        return new Permiso(
            (int)$row['id_permiso'],
            $row['modulo'],
            $row['seccion'],
            $row['accion'],
            $row['descripcion'] ?? null,
        );
    }
}
