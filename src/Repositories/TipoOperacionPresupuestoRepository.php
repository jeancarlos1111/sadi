<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\TipoOperacionPresupuesto;

class TipoOperacionPresupuestoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'tipo_operacion_presupuesto';
    }

    /**
     * @return TipoOperacionPresupuesto[]
     */
    public function all(string $search = ''): array
    {
        $query = $this->query()
                      ->select('*')
                      ->where('eliminado', '=', 0)
                      ->orderBy('denominacion', 'ASC');

        if ($search !== '') {
            $query->where('denominacion', 'LIKE', "%$search%");
        }

        $rows = $query->get();

        return array_map(fn ($row) => $this->mapRowToEntity($row), $rows);
    }

    public function findById(int $id): ?TipoOperacionPresupuesto
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_tipo_operacion_presupuesto', '=', $id)
                    ->where('eliminado', '=', 0)
                    ->first();

        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function save(TipoOperacionPresupuesto $item): bool
    {
        $data = [
            'denominacion' => $item->denominacion,
            'descripcion'  => $item->descripcion,
        ];

        if ($item->id) {
            return $this->query()->where('id_tipo_operacion_presupuesto', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_tipo_operacion_presupuesto', '=', $id)->update(['eliminado' => 1]);
    }

    private function mapRowToEntity(array $row): TipoOperacionPresupuesto
    {
        return new TipoOperacionPresupuesto(
            $row['denominacion'],
            $row['descripcion'] ?? null,
            (int)$row['id_tipo_operacion_presupuesto']
        );
    }
}
