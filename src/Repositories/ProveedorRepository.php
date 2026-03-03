<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Proveedor;
use PDO;

class ProveedorRepository extends Repository
{
    protected function getTable(): string
    {
        return 'proveedor';
    }

    /**
     * @return array
     */
    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                P.id_proveedor, P.rif_proveedor, P.nit_proveedor, P.compania_proveedor, 
                P.id_tipo_organizacion, P.direccion_proveedor, P.telefono_proveedor, P.id_codigo_contable,
                T_O.nombre_tipo_organizacion
            FROM proveedor AS P
            LEFT JOIN tipo_organizacion AS T_O ON P.id_tipo_organizacion = T_O.id_tipo_organizacion
            WHERE P.eliminado = false
        ";

        if ($search !== '') {
            $sql .= " AND (P.rif_proveedor ILIKE :search OR P.compania_proveedor ILIKE :search OR P.direccion_proveedor ILIKE :search OR T_O.nombre_tipo_organizacion ILIKE :search)";
        }
        $sql .= " ORDER BY P.rif_proveedor";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $proveedor = new Proveedor(
                $row['rif_proveedor'],
                $row['compania_proveedor'],
                (int)$row['id_tipo_organizacion'],
                $row['direccion_proveedor'],
                $row['telefono_proveedor'],
                $row['nit_proveedor'],
                $row['id_codigo_contable'] ? (int)$row['id_codigo_contable'] : null,
                (int)$row['id_proveedor']
            );
            $results[] = [
                'entity' => $proveedor,
                'nombre_tipo_organizacion' => $row['nombre_tipo_organizacion'],
            ];
        }

        return $results;
    }

    public function findById(int $id): ?Proveedor
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_proveedor', '=', $id)
                    ->where('eliminado', '=', 0)
                    ->first();

        if (!$row) {
            return null;
        }

        return new Proveedor(
            $row['rif_proveedor'],
            $row['compania_proveedor'],
            (int)$row['id_tipo_organizacion'],
            $row['direccion_proveedor'],
            $row['telefono_proveedor'],
            $row['nit_proveedor'],
            $row['id_codigo_contable'] ? (int)$row['id_codigo_contable'] : null,
            (int)$row['id_proveedor']
        );
    }

    public function save(Proveedor $item): bool
    {
        $data = [
            'rif_proveedor'        => $item->rif,
            'nit_proveedor'        => $item->nit,
            'compania_proveedor'   => $item->compania,
            'id_tipo_organizacion' => $item->idTipoOrganizacion,
            'direccion_proveedor'  => $item->direccion,
            'telefono_proveedor'   => $item->telefono,
            'id_codigo_contable'   => $item->idCodigoContable,
        ];

        if ($item->id) {
            return $this->query()->where('id_proveedor', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_proveedor', '=', $id)->update(['eliminado' => 1]);
    }
}
