<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\TipoServicio;

class TipoServicioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'tipo_servicio';
    }

    /**
     * @return TipoServicio[]
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

    public function findById(int $id): ?TipoServicio
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_tipo_servicio', '=', $id)
                    ->where('eliminado', '=', 0)
                    ->first();

        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function save(TipoServicio $item): bool
    {
        $data = [
            'denominacion' => $item->denominacion,
            'descripcion'  => $item->descripcion,
        ];

        if ($item->id) {
            return $this->query()->where('id_tipo_servicio', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_tipo_servicio', '=', $id)->update(['eliminado' => 1]);
    }

    private function mapRowToEntity(array $row): TipoServicio
    {
        return new TipoServicio(
            $row['denominacion'],
            $row['descripcion'] ?? null,
            (int)$row['id_tipo_servicio']
        );
    }
}
