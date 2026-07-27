<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\DespachoAlmacen;
use Exception;
use PDO;

class DespachoAlmacenRepository extends Repository
{
    protected function getTable(): string
    {
        return 'despacho_almacen';
    }

    public function all(string $search = '', int $limit = 100, int $offset = 0): array
    {
        $db = $this->getPdo();
        $where = "WHERE d.eliminado = false";
        $params = [];

        if ($search !== '') {
            $where .= " AND (d.numero_despacho ILIKE :search OR u.nombre_ua ILIKE :search OR d.solicitante ILIKE :search)";
            $params[':search'] = "%$search%";
        }

        $sql = "SELECT d.*, u.denominacion as unidad_administrativa, us.usuario as usuario_despacha 
                FROM despacho_almacen d
                JOIN unidad_administrativa u ON d.id_unidad_administrativa = u.id_unidad_administrativa
                JOIN usuario us ON d.id_usuario_despacha = us.id_usuario
                $where
                ORDER BY d.id_despacho_almacen DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("
            SELECT d.*, u.denominacion as unidad_administrativa, us.usuario as usuario_despacha 
            FROM despacho_almacen d
            JOIN unidad_administrativa u ON d.id_unidad_administrativa = u.id_unidad_administrativa
            JOIN usuario us ON d.id_usuario_despacha = us.id_usuario
            WHERE d.id_despacho_almacen = ? AND d.eliminado = false
        ");
        $stmt->execute([$id]);
        $cabecera = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cabecera) {
            return null;
        }

        $stmtDetalles = $db->prepare("
            SELECT dd.*, a.denominacion_a, udm.denominacion_udm
            FROM despacho_almacen_detalle dd
            JOIN articulo a ON dd.id_articulo = a.id_articulo
            JOIN unidades_de_medida udm ON a.id_unidades_de_medida = udm.id_unidades_de_medida
            WHERE dd.id_despacho_almacen = ?
        ");
        $stmtDetalles->execute([$id]);
        $cabecera['detalles'] = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

        return $cabecera;
    }

    public function getInsumosDisponibles(): array
    {
        $db = $this->getPdo();
        // Obtener insumos (tipo 2) que tengan stock > 0
        // Buscamos el último registro en inventario_insumos por cada articulo para obtener el stock actual
        $sql = "
            SELECT a.id_articulo, a.denominacion_a, udm.denominacion_udm,
                   COALESCE((
                       SELECT cantidad_ii 
                       FROM inventario_insumos 
                       WHERE id_articulo = a.id_articulo 
                       ORDER BY id_inventario_insumos DESC 
                       LIMIT 1
                   ), 0) as stock
            FROM articulo a
            JOIN tipo_de_articulo tda ON a.id_tipo_de_articulo = tda.id_tipo_de_articulo
            JOIN unidades_de_medida udm ON a.id_unidades_de_medida = udm.id_unidades_de_medida
            WHERE tda.tipo_tda = 2 AND a.eliminado = false
            HAVING COALESCE((
                       SELECT cantidad_ii 
                       FROM inventario_insumos 
                       WHERE id_articulo = a.id_articulo 
                       ORDER BY id_inventario_insumos DESC 
                       LIMIT 1
                   ), 0) > 0
            ORDER BY a.denominacion_a ASC
        ";

        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function procesarDespacho(DespachoAlmacen $despacho): int
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            // 1. Insertar Cabecera
            $stmtCabecera = $db->prepare("
                INSERT INTO despacho_almacen (numero_despacho, fecha_despacho, id_unidad_administrativa, solicitante, id_usuario_despacha, observaciones, estado)
                VALUES (:numero, :fecha, :unidad, :solicitante, :usuario, :obs, :estado)
            ");
            $stmtCabecera->execute([
                ':numero' => $despacho->numeroDespacho,
                ':fecha' => $despacho->fechaDespacho,
                ':unidad' => $despacho->idUnidadAdministrativa,
                ':solicitante' => $despacho->solicitante,
                ':usuario' => $despacho->idUsuarioDespacha,
                ':obs' => $despacho->observaciones,
                ':estado' => $despacho->estado
            ]);

            $idDespacho = (int)$db->lastInsertId();

            // 2. Procesar Detalles y Stock
            $stmtDetalle = $db->prepare("
                INSERT INTO despacho_almacen_detalle (id_despacho_almacen, id_articulo, cantidad_despachada)
                VALUES (?, ?, ?)
            ");

            $stmtStock = $db->prepare("
                SELECT cantidad_ii FROM inventario_insumos 
                WHERE id_articulo = ? 
                ORDER BY id_inventario_insumos DESC LIMIT 1
            ");

            $stmtNuevoStock = $db->prepare("
                INSERT INTO inventario_insumos (id_articulo, fecha_modificacion_ii, cantidad_ii, minimo_ii, id_orden_de_compra)
                VALUES (?, ?, ?, 10, NULL)
            ");

            $stmtUpdateArticulo = $db->prepare("
                UPDATE articulo SET stock_actual = ? WHERE id_articulo = ?
            ");

            $stmtMovimiento = $db->prepare("
                INSERT INTO inventario_movimiento (id_articulo, fecha, tipo_movimiento, cantidad)
                VALUES (?, ?, 'SALIDA', ?)
            ");

            foreach ($despacho->detalles as $detalle) {
                $idArticulo = (int)$detalle['id_articulo'];
                $cantidadDespachada = (float)$detalle['cantidad'];

                if ($cantidadDespachada <= 0) continue;

                // Verificar stock actual
                $stmtStock->execute([$idArticulo]);
                $stockActual = (float)$stmtStock->fetchColumn();

                if ($stockActual < $cantidadDespachada) {
                    throw new Exception("Stock insuficiente para el artículo ID: $idArticulo. Solicitado: $cantidadDespachada, Disponible: $stockActual");
                }

                // Insertar detalle de despacho
                $stmtDetalle->execute([$idDespacho, $idArticulo, $cantidadDespachada]);

                // Actualizar inventario_insumos (nuevo saldo)
                $nuevoStock = $stockActual - $cantidadDespachada;
                $stmtNuevoStock->execute([$idArticulo, date('Y-m-d'), $nuevoStock]);
                
                // Actualizar articulo
                $stmtUpdateArticulo->execute([$nuevoStock, $idArticulo]);

                // Registrar en Kardex (inventario_movimiento)
                $stmtMovimiento->execute([$idArticulo, $despacho->fechaDespacho, $cantidadDespachada]);
            }

            $db->commit();
            return $idDespacho;

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
