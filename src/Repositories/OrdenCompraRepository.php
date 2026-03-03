<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\OrdenCompra;
use Exception;
use PDO;

class OrdenCompraRepository extends Repository
{
    protected function getTable(): string
    {
        return 'orden_de_compra';
    }

    /**
     * @return array
     */
    public function all(string $search = '', string $mes = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                O.id_orden_de_compra, O.fecha_odc, O.concepto_odc, O.id_proveedor,
                O.porcentaje_iva_odc, O.monto_base_odc, O.monto_iva_odc, O.monto_total_odc,
                O.contabilizada,
                P.compania_proveedor
            FROM orden_de_compra AS O
            JOIN proveedor AS P ON O.id_proveedor = P.id_proveedor
            WHERE O.eliminado = false
        ";

        if ($mes !== '' && $mes >= '01' && $mes <= '12') {
            $sql .= " AND to_char(O.fecha_odc, 'MM') = :mes";
        }

        if ($search !== '') {
            $sql .= " AND (
                O.concepto_odc ILIKE :search
                OR P.compania_proveedor ILIKE :search
                OR to_char(O.fecha_odc, 'DD/MM/YYYY') ILIKE :search
                OR CAST(O.id_orden_de_compra AS TEXT) ILIKE :search
            )";
        }
        $sql .= " ORDER BY O.fecha_odc DESC, O.id_orden_de_compra ASC";

        $stmt = $db->prepare($sql);
        if ($mes !== '' && $mes >= '01' && $mes <= '12') {
            $stmt->bindValue(':mes', $mes);
        }
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $oc = new OrdenCompra(
                $row['fecha_odc'],
                $row['concepto_odc'],
                (int)$row['id_proveedor'],
                (float)($row['porcentaje_iva_odc'] ?? 0),
                (float)($row['monto_base_odc'] ?? 0),
                (float)($row['monto_iva_odc'] ?? 0),
                (float)($row['monto_total_odc'] ?? 0),
                [],
                (int)$row['id_orden_de_compra']
            );
            $results[] = [
                'entity' => $oc,
                'proveedor' => $row['compania_proveedor'],
                'contabilizada' => (bool) $row['contabilizada'],
            ];
        }

        return $results;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_orden_de_compra', '=', $id)->update(['eliminado' => 1]);
    }

    /**
     * TRANSACTION: Crear Orden de Compra + Detalles + Compromiso Presupuestario (Interno opcional dependiendo como se requiera pero originalmente no lo hacía aquí sino en contabilizar, la orden comprmetía silencioso o no)
     * En el código original lo compromete aqui o calcula algo y luego lo descarta, no, revisemos:
     * en el original SÍ calculaba $compromisosPartidas pero no hacía el UPDATE en el DB commit de crearConTransaccion. Sólo lo hace en contabilizar.
     */
    public function crearConTransaccion(array $cabecera, array $detalles): int
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            // 1. Insertar Cabecera de la Orden
            $stmt = $db->prepare("
                INSERT INTO orden_de_compra 
                (fecha_odc, concepto_odc, id_proveedor, porcentaje_iva_odc, monto_base_odc, monto_iva_odc, monto_total_odc)
                VALUES (:fecha, :concepto, :proovedor, :pctIva, :base, :iva, :total)
            ");
            $stmt->execute([
                ':fecha' => $cabecera['fecha'],
                ':concepto' => $cabecera['concepto'],
                ':proovedor' => $cabecera['id_proveedor'],
                ':pctIva' => $cabecera['porcentaje_iva_odc'],
                ':base' => $cabecera['monto_base_odc'],
                ':iva' => $cabecera['monto_iva_odc'],
                ':total' => $cabecera['monto_total_odc'],
            ]);

            $idOrden = (int)$db->lastInsertId();

            // 2. Insertar Detalles de Artículos
            $stmtDetalle = $db->prepare("
                INSERT INTO articulo_orden_de_compra
                (id_orden_de_compra, id_articulo, cantidad_aodc, costo_aodc, aplica_iva)
                VALUES (:id_orden, :id_articulo, :cantidad, :costo, :aplica_iva)
            ");

            foreach ($detalles as $item) {
                $stmtDetalle->execute([
                    ':id_orden' => $idOrden,
                    ':id_articulo' => $item['id_articulo'],
                    ':cantidad' => $item['cantidad_aodc'],
                    ':costo' => $item['costo_aodc'],
                    ':aplica_iva' => $item['aplica_iva'] ? 1 : 0,
                ]);
            }

            $db->commit();

            return $idOrden;

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
            // Verificar estado
            $stmt = $db->prepare("SELECT contabilizada FROM orden_de_compra WHERE id_orden_de_compra = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() == 1) {
                throw new Exception("La Orden ya está contabilizada.");
            }

            // Obtener porcentaje_iva_odc
            $stmtIva = $db->prepare("SELECT porcentaje_iva_odc FROM orden_de_compra WHERE id_orden_de_compra = ?");
            $stmtIva->execute([$id]);
            $pctIva = (float)$stmtIva->fetchColumn();

            // Calcular compromisos por partida
            $stmtDet = $db->prepare("
                SELECT aodc.cantidad_aodc, aodc.costo_aodc, aodc.aplica_iva, a.id_codigo_plan_unico 
                FROM articulo_orden_de_compra aodc
                JOIN articulo a ON aodc.id_articulo = a.id_articulo
                WHERE aodc.id_orden_de_compra = ?
            ");
            $stmtDet->execute([$id]);

            $compromisosPartidas = [];
            foreach ($stmtDet->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $idPartida = $item['id_codigo_plan_unico'];
                if (!$idPartida) {
                    throw new Exception("El artículo seleccionado no tiene una Partida Presupuestaria asignada. Edite el Catálogo de Artículos.");
                }
                $montoRenglon = $item['cantidad_aodc'] * $item['costo_aodc'];
                if ($item['aplica_iva']) {
                    $montoRenglon += ($montoRenglon * ($pctIva / 100));
                }
                if (!isset($compromisosPartidas[$idPartida])) {
                    $compromisosPartidas[$idPartida] = 0;
                }
                $compromisosPartidas[$idPartida] += $montoRenglon;
            }

            // Comprometer
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

            $db->prepare("UPDATE orden_de_compra SET contabilizada = 1 WHERE id_orden_de_compra = ?")->execute([$id]);
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
            // Actuar solo si está contabilizada
            $stmt = $db->prepare("SELECT contabilizada FROM orden_de_compra WHERE id_orden_de_compra = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() == 0) {
                return true;
            } // Ya reversada

            $stmtIva = $db->prepare("SELECT porcentaje_iva_odc FROM orden_de_compra WHERE id_orden_de_compra = ?");
            $stmtIva->execute([$id]);
            $pctIva = (float)$stmtIva->fetchColumn();

            $stmtDet = $db->prepare("
                SELECT aodc.cantidad_aodc, aodc.costo_aodc, aodc.aplica_iva, a.id_codigo_plan_unico 
                FROM articulo_orden_de_compra aodc
                JOIN articulo a ON aodc.id_articulo = a.id_articulo
                WHERE aodc.id_orden_de_compra = ?
            ");
            $stmtDet->execute([$id]);

            $compromisosPartidas = [];
            foreach ($stmtDet->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $idPartida = $item['id_codigo_plan_unico'];
                if (!$idPartida) {
                    throw new Exception("El artículo seleccionado no tiene una Partida Presupuestaria asignada. Edite el Catálogo de Artículos.");
                }
                $montoRenglon = $item['cantidad_aodc'] * $item['costo_aodc'];
                if ($item['aplica_iva']) {
                    $montoRenglon += ($montoRenglon * ($pctIva / 100));
                }
                if (!isset($compromisosPartidas[$idPartida])) {
                    $compromisosPartidas[$idPartida] = 0;
                }
                $compromisosPartidas[$idPartida] += $montoRenglon;
            }

            // Liberar (reversar compromiso)
            $stmtPresupuesto = $db->prepare("UPDATE presupuesto_gastos SET monto_comprometido = monto_comprometido - :monto WHERE id_codigo_plan_unico = :id_partida");
            foreach ($compromisosPartidas as $idPartida => $monto) {
                $stmtPresupuesto->execute([':monto' => $monto, ':id_partida' => $idPartida]);
            }

            $db->prepare("UPDATE orden_de_compra SET contabilizada = 0 WHERE id_orden_de_compra = ?")->execute([$id]);
            $db->commit();

            return true;
        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }
}
