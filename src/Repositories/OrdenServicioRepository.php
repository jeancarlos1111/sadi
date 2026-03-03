<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\OrdenServicio;
use Exception;
use PDO;

class OrdenServicioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'orden_de_servicio';
    }

    /**
     * @return array
     */
    public function all(string $search = '', string $mes = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT os.*, p.compania_proveedor
            FROM orden_de_servicio os
            JOIN proveedor p ON os.id_proveedor = p.id_proveedor
            WHERE os.eliminado = 0
        ";
        if ($mes !== '') {
            $sql .= " AND strftime('%m', os.fecha_os) = :mes";
        }
        if ($search !== '') {
            $sql .= " AND (os.concepto_os LIKE :s OR p.compania_proveedor LIKE :s)";
        }
        $sql .= " ORDER BY os.fecha_os DESC, os.id_orden_de_servicio DESC";

        $stmt = $db->prepare($sql);
        if ($mes !== '') {
            $stmt->bindValue(':mes', str_pad($mes, 2, '0', STR_PAD_LEFT));
        }
        if ($search !== '') {
            $stmt->bindValue(':s', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = [
                'entity'    => new OrdenServicio(
                    $row['fecha_os'],
                    $row['concepto_os'],
                    (int)$row['id_proveedor'],
                    (float)$row['porcentaje_iva_os'],
                    (float)$row['monto_base_os'],
                    (float)$row['monto_iva_os'],
                    (float)$row['monto_total_os'],
                    (bool)$row['contabilizada'],
                    [],
                    (int)$row['id_orden_de_servicio']
                ),
                'proveedor' => $row['compania_proveedor'],
            ];
        }

        return $results;
    }

    public function findById(int $id): ?OrdenServicio
    {
        $db = $this->getPdo();

        $stmt = $db->prepare("SELECT * FROM orden_de_servicio WHERE id_orden_de_servicio = :id AND eliminado = 0");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $stmtDet = $db->prepare("
            SELECT sods.*, s.denominacion 
            FROM servicio_orden_de_servicio sods 
            JOIN servicio s ON sods.id_servicio = s.id_servicio 
            WHERE sods.id_orden_de_servicio = :id
        ");
        $stmtDet->execute(['id' => $id]);
        $servicios = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return new OrdenServicio(
            $row['fecha_os'],
            $row['concepto_os'],
            (int)$row['id_proveedor'],
            (float)$row['porcentaje_iva_os'],
            (float)$row['monto_base_os'],
            (float)$row['monto_iva_os'],
            (float)$row['monto_total_os'],
            (bool)$row['contabilizada'],
            $servicios,
            (int)$row['id_orden_de_servicio']
        );
    }

    public function crear(array $cabecera, array $detalles): int
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                INSERT INTO orden_de_servicio 
                (fecha_os, concepto_os, id_proveedor, porcentaje_iva_os, monto_base_os, monto_iva_os, monto_total_os) 
                VALUES (:f, :c, :p, :piva, :base, :iva, :total)
            ");
            $stmt->execute([
                ':f'     => $cabecera['fecha'],
                ':c'     => $cabecera['concepto'],
                ':p'     => $cabecera['id_proveedor'],
                ':piva'  => $cabecera['porcentaje_iva'],
                ':base'  => $cabecera['monto_base'],
                ':iva'   => $cabecera['monto_iva'],
                ':total' => $cabecera['monto_total'],
            ]);
            $idOS = (int)$db->lastInsertId();

            $stmtDet = $db->prepare("
                INSERT INTO servicio_orden_de_servicio 
                (id_orden_de_servicio, id_servicio, cantidad_sods, costo_sods, aplica_iva) 
                VALUES (:os, :s, :c, :co, :iva)
            ");
            foreach ($detalles as $d) {
                $stmtDet->execute([
                    ':os'  => $idOS,
                    ':s'   => $d['id_servicio'],
                    ':c'   => $d['cantidad'],
                    ':co'  => $d['costo'],
                    ':iva' => $d['aplica_iva'] ? 1 : 0,
                ]);
            }
            $db->commit();

            return $idOS;
        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }

    public function contabilizar(int $id): bool
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT contabilizada FROM orden_de_servicio WHERE id_orden_de_servicio = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() == 1) {
                throw new Exception("La Orden ya está contabilizada.");
            }

            $stmtIva = $db->prepare("SELECT porcentaje_iva_os FROM orden_de_servicio WHERE id_orden_de_servicio = ?");
            $stmtIva->execute([$id]);
            $pctIva = (float)$stmtIva->fetchColumn();

            $stmtDet = $db->prepare("
                SELECT sods.cantidad_sods, sods.costo_sods, sods.aplica_iva, s.id_codigo_plan_unico 
                FROM servicio_orden_de_servicio sods
                JOIN servicio s ON sods.id_servicio = s.id_servicio
                WHERE sods.id_orden_de_servicio = ?
            ");
            $stmtDet->execute([$id]);

            $compromisosPartidas = [];
            foreach ($stmtDet->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $idPartida = $item['id_codigo_plan_unico'];
                if (!$idPartida) {
                    throw new Exception("El servicio seleccionado no tiene una Partida Presupuestaria asignada. Edite el Catálogo de Servicios.");
                }
                $montoRenglon = $item['cantidad_sods'] * $item['costo_sods'];
                if ($item['aplica_iva']) {
                    $montoRenglon += ($montoRenglon * ($pctIva / 100));
                }
                if (!isset($compromisosPartidas[$idPartida])) {
                    $compromisosPartidas[$idPartida] = 0;
                }
                $compromisosPartidas[$idPartida] += $montoRenglon;
            }

            $stmtPresupuesto = $db->prepare("UPDATE presupuesto_gastos SET monto_comprometido = monto_comprometido + :monto WHERE id_codigo_plan_unico = :id_partida");
            foreach ($compromisosPartidas as $idPartida => $montoComprometer) {
                $stmtCheck = $db->prepare("SELECT (monto_asignado - monto_comprometido) as disponible FROM presupuesto_gastos WHERE id_codigo_plan_unico = ?");
                $stmtCheck->execute([$idPartida]);
                $disponible = (float)$stmtCheck->fetchColumn();

                if ($montoComprometer > $disponible) {
                    throw new Exception("Insuficiente disponibilidad presupuestaria para la partida ID $idPartida. Faltan Bs " . number_format($montoComprometer - $disponible, 2, ',', '.'));
                }
                $stmtPresupuesto->execute([':monto' => $montoComprometer, ':id_partida' => $idPartida]);
            }

            $db->prepare("UPDATE orden_de_servicio SET contabilizada = 1 WHERE id_orden_de_servicio = ?")->execute([$id]);
            $db->commit();

            return true;
        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }

    public function reversar(int $id): bool
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT contabilizada FROM orden_de_servicio WHERE id_orden_de_servicio = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() == 0) {
                return true;
            }

            $stmtIva = $db->prepare("SELECT porcentaje_iva_os FROM orden_de_servicio WHERE id_orden_de_servicio = ?");
            $stmtIva->execute([$id]);
            $pctIva = (float)$stmtIva->fetchColumn();

            $stmtDet = $db->prepare("
                SELECT sods.cantidad_sods, sods.costo_sods, sods.aplica_iva, s.id_codigo_plan_unico 
                FROM servicio_orden_de_servicio sods
                JOIN servicio s ON sods.id_servicio = s.id_servicio
                WHERE sods.id_orden_de_servicio = ?
            ");
            $stmtDet->execute([$id]);

            $compromisosPartidas = [];
            foreach ($stmtDet->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $idPartida = $item['id_codigo_plan_unico'];
                if ($idPartida) {
                    $montoRenglon = $item['cantidad_sods'] * $item['costo_sods'];
                    if ($item['aplica_iva']) {
                        $montoRenglon += ($montoRenglon * ($pctIva / 100));
                    }
                    if (!isset($compromisosPartidas[$idPartida])) {
                        $compromisosPartidas[$idPartida] = 0;
                    }
                    $compromisosPartidas[$idPartida] += $montoRenglon;
                }
            }

            $stmtPresupuesto = $db->prepare("UPDATE presupuesto_gastos SET monto_comprometido = monto_comprometido - :monto WHERE id_codigo_plan_unico = :id_partida");
            foreach ($compromisosPartidas as $idPartida => $monto) {
                $stmtPresupuesto->execute([':monto' => $monto, ':id_partida' => $idPartida]);
            }

            $db->prepare("UPDATE orden_de_servicio SET contabilizada = 0 WHERE id_orden_de_servicio = ?")->execute([$id]);
            $db->commit();

            return true;
        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_orden_de_servicio', '=', $id)->where('contabilizada', '=', 0)->update(['eliminado' => 1]);
    }
}
