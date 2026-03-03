<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\RequisicionServicios;
use PDO;

class RequisicionServiciosRepository extends Repository
{
    protected function getTable(): string
    {
        return 'requisicion_servicios';
    }

    /**
     * @return array
     */
    public function all(string $search = '', string $mes = ''): array
    {
        $db = $this->getPdo();
        $sql = "SELECT * FROM requisicion_servicios WHERE eliminado = 0";
        if ($mes !== '') {
            $sql .= " AND strftime('%m', fecha_rs) = :mes";
        }
        if ($search !== '') {
            $sql .= " AND concepto_rs LIKE :s";
        }
        $sql .= " ORDER BY fecha_rs DESC, id_requisicion_servicios DESC";

        $stmt = $db->prepare($sql);
        if ($mes !== '') {
            $stmt->bindValue(':mes', str_pad($mes, 2, '0', STR_PAD_LEFT));
        }
        if ($search !== '') {
            $stmt->bindValue(':s', "%$search%");
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?RequisicionServicios
    {
        $db = $this->getPdo();

        $stmt = $db->prepare("SELECT * FROM requisicion_servicios WHERE id_requisicion_servicios = :id AND eliminado = 0");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $stmtDet = $db->prepare("SELECT * FROM servicio_requisicion_servicios WHERE id_requisicion_servicios = :id");
        $stmtDet->execute(['id' => $id]);
        $servicios = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return new RequisicionServicios(
            $row['fecha_rs'],
            $row['concepto_rs'],
            (int)$row['id_estructura_presupuestaria'],
            $servicios,
            (int)$row['id_requisicion_servicios']
        );
    }

    public function save(RequisicionServicios $item): bool
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            if ($item->id) {
                $stmt = $db->prepare("
                    UPDATE requisicion_servicios 
                    SET fecha_rs = :f, concepto_rs = :c, id_estructura_presupuestaria = :ep 
                    WHERE id_requisicion_servicios = :id
                ");
                $stmt->execute([
                    'f'  => $item->fecha,
                    'c'  => $item->concepto,
                    'ep' => $item->idEstructura,
                    'id' => $item->id,
                ]);
                $db->prepare("DELETE FROM servicio_requisicion_servicios WHERE id_requisicion_servicios = :id")->execute(['id' => $item->id]);
                $idRS = $item->id;
            } else {
                $stmt = $db->prepare("
                    INSERT INTO requisicion_servicios (fecha_rs, concepto_rs, id_estructura_presupuestaria) 
                    VALUES (:f, :c, :ep)
                ");
                $stmt->execute([
                    'f'  => $item->fecha,
                    'c'  => $item->concepto,
                    'ep' => $item->idEstructura,
                ]);
                $idRS = (int)$db->lastInsertId();
                $item->id = $idRS;
            }

            $stmtDet = $db->prepare("
                INSERT INTO servicio_requisicion_servicios (id_requisicion_servicios, id_servicio, cantidad_srs) 
                VALUES (:r, :s, :c)
            ");
            foreach ($item->servicios as $srv) {
                $stmtDet->execute([
                    ':r' => $idRS,
                    ':s' => (int)$srv['id_servicio'],
                    ':c' => (float)$srv['cantidad'],
                ]);
            }

            $db->commit();

            return true;
        } catch (\Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_requisicion_servicios', '=', $id)->update(['eliminado' => 1]);
    }
}
