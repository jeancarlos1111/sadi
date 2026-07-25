<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ActaRecepcion;
use App\Models\ActaRecepcionDetalle;
use App\Database\Connection;
use PDO;
use Exception;

class ActaRecepcionRepository
{
    public function __construct(private readonly PDO $db) {}

    public function saveConDetalles(ActaRecepcion $acta, array $detalles): int
    {
        try {
            $this->db->beginTransaction();
            
            // Insertar Acta
            $stmt = $this->db->prepare("
                INSERT INTO acta_recepcion (
                    numero_acta, fecha_recepcion, id_orden_de_compra, id_usuario_receptor, conformidad, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?) RETURNING id_acta_recepcion
            ");
            
            $stmt->execute([
                $acta->numeroActa,
                $acta->fechaRecepcion,
                $acta->idOrdenDeCompra,
                $acta->idUsuarioReceptor,
                $acta->conformidad ? 'true' : 'false',
                $acta->observaciones
            ]);
            
            $idActa = (int)$stmt->fetchColumn();
            
            // Insertar Detalles y Movimientos
            $stmtDet = $this->db->prepare("
                INSERT INTO acta_recepcion_detalle (id_acta_recepcion, id_articulo, cantidad_recibida, estado_fisico)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmtMov = $this->db->prepare("
                INSERT INTO inventario_movimiento (id_articulo, fecha, tipo_movimiento, cantidad, id_acta_recepcion)
                VALUES (?, CURRENT_DATE, 'ENTRADA', ?, ?)
            ");
            
            $stmtUpd = $this->db->prepare("
                UPDATE articulo SET stock_actual = COALESCE(stock_actual, 0) + ? WHERE id_articulo = ?
            ");

            foreach ($detalles as $det) {
                if (!$det instanceof ActaRecepcionDetalle) continue;
                
                // Detalle
                $stmtDet->execute([
                    $idActa,
                    $det->idArticulo,
                    $det->cantidadRecibida,
                    $det->estadoFisico
                ]);
                
                // Movimiento Kardex
                $stmtMov->execute([
                    $det->idArticulo,
                    $det->cantidadRecibida,
                    $idActa
                ]);
                
                // Aumentar Stock
                $stmtUpd->execute([
                    $det->cantidadRecibida,
                    $det->idArticulo
                ]);
            }
            
            $this->db->commit();
            return $idActa;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
