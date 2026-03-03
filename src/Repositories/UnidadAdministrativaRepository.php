<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\UnidadAdministrativa;

class UnidadAdministrativaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'unidad_administrativa';
    }

    /**
     * @return UnidadAdministrativa[]
     */
    public function all(string $search = ''): array
    {
        $query = $this->query()
                      ->select('*')
                      ->where('eliminado', '=', 0)
                      ->orderBy('codigo', 'ASC');

        if ($search !== '') {
            $db = $this->getPdo();
            $sql = "SELECT * FROM unidad_administrativa WHERE eliminado = 0 AND (denominacion LIKE :s OR codigo LIKE :s) ORDER BY codigo";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':s', "%$search%");
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } else {
            $rows = $query->get();
        }

        return array_map(fn ($row) => $this->mapRowToEntity($row), $rows);
    }

    public function findById(int $id): ?UnidadAdministrativa
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_unidad_administrativa', '=', $id)
                    ->where('eliminado', '=', 0)
                    ->first();

        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function save(UnidadAdministrativa $item): bool
    {
        $data = [
            'codigo'       => $item->codigo,
            'denominacion' => $item->denominacion,
        ];

        if ($item->id) {
            return $this->query()->where('id_unidad_administrativa', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_unidad_administrativa', '=', $id)->update(['eliminado' => 1]);
    }

    private function mapRowToEntity(array $row): UnidadAdministrativa
    {
        return new UnidadAdministrativa(
            $row['codigo'],
            $row['denominacion'],
            (int)$row['id_unidad_administrativa']
        );
    }
}
