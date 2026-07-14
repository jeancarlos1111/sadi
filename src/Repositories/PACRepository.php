<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\PAC;
use PDO;

class PACRepository extends Repository
{
    protected function getTable(): string
    {
        return 'pac';
    }

    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT p.*, a.denominacion_a, 
                   pr.denominacion as proyecto_desc, 
                   ac.denominacion as ac_desc
            FROM pac p
            JOIN articulo a ON p.id_articulo = a.id_articulo
            LEFT JOIN proyecto pr ON p.id_proyecto = pr.id_proyecto
            LEFT JOIN accion_centralizada ac ON p.id_accion_centralizada = ac.id_accion_centralizada
            WHERE p.eliminado = false
        ";

        if ($search !== '') {
            $sql .= " AND (a.denominacion_a ILIKE :s OR pr.denominacion ILIKE :s OR ac.denominacion ILIKE :s)";
        }
        $sql .= " ORDER BY p.id_pac DESC";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':s', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = [
                'entity' => new PAC(
                    $row['id_proyecto'] ? (int)$row['id_proyecto'] : null,
                    $row['id_accion_centralizada'] ? (int)$row['id_accion_centralizada'] : null,
                    (int)$row['id_articulo'],
                    (float)$row['cantidad_anual'],
                    (float)$row['trim_1'],
                    (float)$row['trim_2'],
                    (float)$row['trim_3'],
                    (float)$row['trim_4'],
                    (float)$row['costo_estimado'],
                    $row['estatus'],
                    (int)$row['id_pac']
                ),
                'articulo_desc' => $row['denominacion_a'],
                'proyecto_desc' => $row['proyecto_desc'],
                'ac_desc'       => $row['ac_desc'],
            ];
        }

        return $results;
    }

    public function findById(int $id): ?PAC
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM pac WHERE id_pac = :id AND eliminado = false");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new PAC(
            $row['id_proyecto'] ? (int)$row['id_proyecto'] : null,
            $row['id_accion_centralizada'] ? (int)$row['id_accion_centralizada'] : null,
            (int)$row['id_articulo'],
            (float)$row['cantidad_anual'],
            (float)$row['trim_1'],
            (float)$row['trim_2'],
            (float)$row['trim_3'],
            (float)$row['trim_4'],
            (float)$row['costo_estimado'],
            $row['estatus'],
            (int)$row['id_pac']
        );
    }

    public function save(PAC $item): bool|string|int
    {
        $data = [
            'id_proyecto' => $item->id_proyecto,
            'id_accion_centralizada' => $item->id_accion_centralizada,
            'id_articulo' => $item->id_articulo,
            'cantidad_anual' => $item->cantidad_anual,
            'trim_1' => $item->trim_1,
            'trim_2' => $item->trim_2,
            'trim_3' => $item->trim_3,
            'trim_4' => $item->trim_4,
            'costo_estimado' => $item->costo_estimado,
            'estatus' => $item->estatus,
        ];

        if ($item->id) {
            return $this->query()->where('id_pac', '=', $item->id)->update($data);
        } else {
            return $this->query()->insert($data);
        }
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_pac', '=', $id)->update(['eliminado' => 'true']);
    }

    public function aprobar(int $id): bool
    {
        return $this->query()->where('id_pac', '=', $id)->update(['estatus' => 'APROBADO']);
    }
}
