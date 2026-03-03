<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Almacen;
use PDO;

class AlmacenRepository extends Repository
{
    protected function getTable(): string
    {
        return 'ubicacion_articulo'; // According to the original query
    }

    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT id_ubicacion_articulo, denominacion_ua FROM ubicacion_articulo WHERE eliminado = false ORDER BY denominacion_ua");

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new Almacen(
                (int)$row['id_ubicacion_articulo'],
                $row['denominacion_ua']
            );
        }

        return $results;
    }
}
