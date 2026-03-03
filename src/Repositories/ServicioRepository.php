<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Servicio;
use PDO;

class ServicioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'servicio';
    }

    /**
     * @return array
     */
    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT s.*, ts.denominacion AS nombre_tipo, puc.codigo_plan_unico, puc.denominacion AS denominacion_puc
            FROM servicio s
            JOIN tipo_servicio ts ON s.id_tipo_servicio = ts.id_tipo_servicio
            LEFT JOIN plan_unico_cuentas puc ON s.id_codigo_plan_unico = puc.id_codigo_plan_unico
            WHERE s.eliminado = 0
        ";

        if ($search !== '') {
            $sql .= " AND s.denominacion ILIKE :s";
        }
        $sql .= " ORDER BY s.denominacion";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':s', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $servicio = new Servicio(
                $row['denominacion'],
                $row['descripcion'] ?? null,
                (int)$row['id_tipo_servicio'],
                (bool)$row['aplicar_iva'],
                $row['id_codigo_plan_unico'] ? (int)$row['id_codigo_plan_unico'] : null,
                (int)$row['id_servicio']
            );
            $results[] = [
                'entity'      => $servicio,
                'nombre_tipo' => $row['nombre_tipo'],
                'partida'     => $row['codigo_plan_unico'] ? ($row['codigo_plan_unico'] . ' - ' . $row['denominacion_puc']) : 'N/A',
            ];
        }

        return $results;
    }

    public function findById(int $id): ?Servicio
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_servicio', '=', $id)
                    ->where('eliminado', '=', 0)
                    ->first();

        if (!$row) {
            return null;
        }

        return new Servicio(
            $row['denominacion'],
            $row['descripcion'] ?? null,
            (int)$row['id_tipo_servicio'],
            (bool)$row['aplicar_iva'],
            $row['id_codigo_plan_unico'] ? (int)$row['id_codigo_plan_unico'] : null,
            (int)$row['id_servicio']
        );
    }

    public function save(Servicio $item): bool
    {
        $iva = $item->aplicarIva ? 1 : 0;
        $data = [
            'denominacion'         => $item->denominacion,
            'descripcion'          => $item->descripcion,
            'id_tipo_servicio'     => $item->idTipoServicio,
            'aplicar_iva'          => $iva,
            'id_codigo_plan_unico' => $item->idCodigoPlanUnico,
        ];

        if ($item->id) {
            return $this->query()->where('id_servicio', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_servicio', '=', $id)->update(['eliminado' => 1]);
    }
}
