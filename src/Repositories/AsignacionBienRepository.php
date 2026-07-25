<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AsignacionBien;
use PDO;
use Exception;

class AsignacionBienRepository
{
    public function __construct(private readonly PDO $db) {}

    public function save(AsignacionBien $asignacion): int
    {
        try {
            $this->db->beginTransaction();

            // Verificar Stock
            $stmtStock = $this->db->prepare("SELECT stock_actual FROM articulo WHERE id_articulo = ? FOR UPDATE");
            $stmtStock->execute([$asignacion->idArticulo]);
            $stock = (float)$stmtStock->fetchColumn();
            
            if ($stock <= 0) {
                throw new Exception("No hay stock suficiente para asignar este bien.");
            }

            // Insertar Asignacion
            $stmt = $this->db->prepare("
                INSERT INTO asignacion_bien (
                    numero_asignacion, id_articulo, cedula_responsable, id_unidad_administrativa, fecha_asignacion, estado_asignacion
                ) VALUES (?, ?, ?, ?, ?, ?) RETURNING id_asignacion
            ");
            
            $stmt->execute([
                $asignacion->numeroAsignacion,
                $asignacion->idArticulo,
                $asignacion->cedulaResponsable,
                $asignacion->idUnidadAdministrativa,
                $asignacion->fechaAsignacion,
                $asignacion->estadoAsignacion
            ]);
            
            $idAsignacion = (int)$stmt->fetchColumn();

            // Movimiento Kardex
            $stmtMov = $this->db->prepare("
                INSERT INTO inventario_movimiento (id_articulo, fecha, tipo_movimiento, cantidad, id_asignacion)
                VALUES (?, CURRENT_DATE, 'SALIDA', 1, ?)
            ");
            $stmtMov->execute([$asignacion->idArticulo, $idAsignacion]);

            // Descontar Stock
            $stmtUpd = $this->db->prepare("
                UPDATE articulo SET stock_actual = stock_actual - 1 WHERE id_articulo = ?
            ");
            $stmtUpd->execute([$asignacion->idArticulo]);

            $this->db->commit();
            return $idAsignacion;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
