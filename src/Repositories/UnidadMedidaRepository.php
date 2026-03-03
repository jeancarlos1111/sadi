<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\UnidadMedida;

class UnidadMedidaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'unidades_de_medida';
    }

    /**
     * @return UnidadMedida[]
     */
    public function all(string $search = ''): array
    {
        $query = $this->query()
                      ->select('*')
                      ->where('eliminado', '=', 0)
                      ->orderBy('denominacion_udm', 'ASC');

        // Aplicamos el formato con OR al ser una búsqueda en múltiples campos
        if ($search !== '') {
            $db = $this->getPdo();
            $sql = "SELECT * FROM unidades_de_medida WHERE eliminado = 0 AND (denominacion_udm LIKE :s OR unidades_udm LIKE :s) ORDER BY denominacion_udm";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':s', "%$search%");
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } else {
            $rows = $query->get();
        }

        return array_map(fn ($row) => $this->mapRowToEntity($row), $rows);
    }

    public function findById(int $id): ?UnidadMedida
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_unidades_de_medida', '=', $id)
                    ->where('eliminado', '=', 0)
                    ->first();

        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function save(UnidadMedida $item): bool
    {
        $data = [
            'denominacion_udm' => $item->denominacion,
            'unidades_udm'     => $item->unidades,
            'observacion_udm'  => $item->observacion,
        ];

        if ($item->id) {
            return $this->query()->where('id_unidades_de_medida', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_unidades_de_medida', '=', $id)->update(['eliminado' => 1]);
    }

    private function mapRowToEntity(array $row): UnidadMedida
    {
        return new UnidadMedida(
            $row['denominacion_udm'],
            $row['unidades_udm'],
            $row['observacion_udm'] ?? null,
            (int)$row['id_unidades_de_medida']
        );
    }
}
