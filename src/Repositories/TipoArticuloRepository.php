<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\TipoArticulo;

class TipoArticuloRepository extends Repository
{
    protected function getTable(): string
    {
        return 'tipo_de_articulo';
    }

    /**
     * @return TipoArticulo[]
     */
    public function all(string $search = ''): array
    {
        $query = $this->query()
                      ->select('*')
                      ->where('eliminado', '=', 0)
                      ->orderBy('denominacion_tda', 'ASC');

        if ($search !== '') {
            $query->where('denominacion_tda', 'LIKE', "%$search%");
        }

        $rows = $query->get();

        return array_map(fn ($row) => $this->mapRowToEntity($row), $rows);
    }

    public function findById(int $id): ?TipoArticulo
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_tipo_de_articulo', '=', $id)
                    ->where('eliminado', '=', 0)
                    ->first();

        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function save(TipoArticulo $item): bool
    {
        $data = [
            'denominacion_tda' => $item->denominacion,
            'descripcion_tda'  => $item->descripcion,
            'tipo_tda'         => $item->tipo,
        ];

        if ($item->id) {
            return $this->query()->where('id_tipo_de_articulo', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_tipo_de_articulo', '=', $id)->update(['eliminado' => 1]);
    }

    private function mapRowToEntity(array $row): TipoArticulo
    {
        return new TipoArticulo(
            $row['denominacion_tda'],
            $row['descripcion_tda'] ?? null,
            (int)($row['tipo_tda'] ?? 1),
            (int)$row['id_tipo_de_articulo']
        );
    }
}
