<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\InventarioInsumo;
use PDO;

class InventarioInsumoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'inventario_insumos';
    }

    /**
     * @return array
     */
    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                II.id_inventario_insumos, II.id_articulo, II.cantidad_ii, II.minimo_ii,
                A.denominacion_a, UM.unidades_udm
            FROM inventario_insumos AS II
            JOIN articulo AS A ON II.id_articulo = A.id_articulo
            LEFT JOIN unidades_de_medida AS UM ON A.id_unidades_de_medida = UM.id_unidades_de_medida
            WHERE II.eliminado = false
        ";

        if ($search !== '') {
            $sql .= " AND (
                A.denominacion_a ILIKE :search
            )";
        }
        $sql .= " ORDER BY A.denominacion_a ASC";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $insumo = new InventarioInsumo(
                (int)$row['id_articulo'],
                (float)($row['cantidad_ii'] ?? 0),
                (float)($row['minimo_ii'] ?? 0),
                (int)$row['id_inventario_insumos']
            );
            $results[] = [
                'entity' => $insumo,
                'articulo_desc' => $row['denominacion_a'],
                'unidad' => $row['unidades_udm'] ?? 'UND',
            ];
        }

        return $results;
    }
}
