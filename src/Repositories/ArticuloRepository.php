<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Articulo;
use PDO;

class ArticuloRepository extends Repository
{
    protected function getTable(): string
    {
        return 'articulo';
    }

    /**
     * @return array
     */
    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                A.id_articulo, A.denominacion_a, A.observacion_a, A.id_tipo_de_articulo, 
                A.id_unidades_de_medida, A.id_codigo_plan_unico, A.aplicar_iva,
                UDM.denominacion_udm, TDA.denominacion_tda
            FROM articulo AS A
            JOIN unidades_de_medida AS UDM ON A.id_unidades_de_medida = UDM.id_unidades_de_medida
            JOIN tipo_de_articulo AS TDA ON A.id_tipo_de_articulo = TDA.id_tipo_de_articulo
            WHERE A.eliminado = false
        ";

        if ($search !== '') {
            $sql .= " AND (A.denominacion_a ILIKE :search OR CAST(A.id_articulo AS TEXT) ILIKE :search)";
        }
        $sql .= " ORDER BY A.denominacion_a";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $articulo = new Articulo(
                $row['denominacion_a'],
                $row['observacion_a'] ?? '',
                (int)$row['id_tipo_de_articulo'],
                (int)$row['id_unidades_de_medida'],
                $row['id_codigo_plan_unico'] ? (int)$row['id_codigo_plan_unico'] : null,
                (bool)$row['aplicar_iva'],
                (int)$row['id_articulo']
            );
            $results[] = [
                'entity' => $articulo,
                'denominacion_udm' => $row['denominacion_udm'],
                'denominacion_tda' => $row['denominacion_tda'],
            ];
        }

        return $results;
    }

    public function findById(int $id): ?Articulo
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_articulo', '=', $id)
                    ->where('eliminado', '=', 0)
                    ->first();

        if (!$row) {
            return null;
        }

        return new Articulo(
            $row['denominacion_a'],
            $row['observacion_a'] ?? '',
            (int)$row['id_tipo_de_articulo'],
            (int)$row['id_unidades_de_medida'],
            $row['id_codigo_plan_unico'] ? (int)$row['id_codigo_plan_unico'] : null,
            (bool)$row['aplicar_iva'],
            (int)$row['id_articulo']
        );
    }

    public function save(Articulo $item): bool
    {
        $ivaStr = $item->aplicarIva ? 'true' : 'false';
        $data = [
            'denominacion_a'        => $item->denominacion,
            'observacion_a'         => $item->observacion,
            'id_tipo_de_articulo'   => $item->idTipoDeArticulo,
            'id_unidades_de_medida' => $item->idUnidadesDeMedida,
            'id_codigo_plan_unico'  => $item->idCodigoPlanUnico,
            'aplicar_iva'           => $ivaStr,
        ];

        if ($item->id) {
            return $this->query()->where('id_articulo', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_articulo', '=', $id)->update(['eliminado' => 1]);
    }
}
