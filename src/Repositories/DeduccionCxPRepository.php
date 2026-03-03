<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\DeduccionCxP;
use PDO;

class DeduccionCxPRepository extends Repository
{
    protected function getTable(): string
    {
        return 'deducciones_cxp';
    }

    /**
     * @return DeduccionCxP[]
     */
    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "SELECT * FROM deducciones_cxp WHERE eliminado = 0";
        if ($search !== '') {
            $sql .= " AND (codigo_deduccion LIKE :s OR denominacion LIKE :s)";
        }
        $sql .= " ORDER BY denominacion";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':s', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new DeduccionCxP(
                $row['codigo_deduccion'],
                $row['denominacion'],
                (float)$row['porcentaje'],
                $row['aplica_sobre'],
                (bool)$row['activo'],
                (int)$row['id_deduccion']
            );
        }

        return $results;
    }

    public function findById(int $id): ?DeduccionCxP
    {
        $row = $this->query()->where('id_deduccion', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return new DeduccionCxP(
            $row['codigo_deduccion'],
            $row['denominacion'],
            (float)$row['porcentaje'],
            $row['aplica_sobre'],
            (bool)$row['activo'],
            (int)$row['id_deduccion']
        );
    }

    public function save(DeduccionCxP $item): bool
    {
        $data = [
            'codigo_deduccion' => $item->codigo,
            'denominacion'     => $item->denominacion,
            'porcentaje'       => $item->porcentaje,
            'aplica_sobre'     => $item->aplicaSobre,
            'activo'           => $item->activo ? 1 : 0,
        ];

        if ($item->id) {
            return $this->query()->where('id_deduccion', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        if ($id) {
            $item->id = (int)$id;

            return true;
        }

        return false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_deduccion', '=', $id)->update(['eliminado' => 1]);
    }
}
