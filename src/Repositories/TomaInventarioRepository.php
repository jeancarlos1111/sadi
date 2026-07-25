<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\TomaInventario;
use App\Models\TomaInventarioDetalle;
use Exception;
use PDO;

class TomaInventarioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'toma_inventario';
    }

    public function all(string $search = '', int $limit = 100, int $offset = 0): array
    {
        $db = $this->getPdo();
        $where = "WHERE eliminado = false";
        $params = [];

        if ($search !== '') {
            $where .= " AND responsable ILIKE :search";
            $params[':search'] = "%$search%";
        }

        $sql = "SELECT * FROM toma_inventario $where ORDER BY id_toma DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?TomaInventario
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM toma_inventario WHERE id_toma = ? AND eliminado = false");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new TomaInventario(
            $row['fecha_toma'],
            $row['responsable'],
            $row['estado'],
            $row['observaciones'],
            $row['fecha_cierre'],
            (int)$row['id_toma']
        );
    }

    public function save(TomaInventario $item): int
    {
        $db = $this->getPdo();
        
        if ($item->idToma) {
            $stmt = $db->prepare("
                UPDATE toma_inventario SET 
                    fecha_toma = :fecha,
                    responsable = :resp,
                    estado = :estado,
                    observaciones = :obs,
                    fecha_cierre = :cierre
                WHERE id_toma = :id
            ");
            $stmt->execute([
                ':fecha' => $item->fechaToma,
                ':resp' => $item->responsable,
                ':estado' => $item->estado,
                ':obs' => $item->observaciones,
                ':cierre' => $item->fechaCierre,
                ':id' => $item->idToma
            ]);
            return $item->idToma;
        }

        $stmt = $db->prepare("
            INSERT INTO toma_inventario (fecha_toma, responsable, estado, observaciones)
            VALUES (:fecha, :resp, :estado, :obs)
        ");
        $stmt->execute([
            ':fecha' => $item->fechaToma,
            ':resp' => $item->responsable,
            ':estado' => $item->estado,
            ':obs' => $item->observaciones
        ]);
        
        return (int)$db->lastInsertId();
    }

    public function getDetalles(int $idToma): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT d.*, a.denominacion_a 
            FROM toma_inventario_detalle d
            JOIN articulo a ON d.id_articulo = a.id_articulo
            WHERE d.id_toma = ? AND d.eliminado = false
            ORDER BY d.tipo, a.denominacion_a
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idToma]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function inicializarConteo(int $idToma): void
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            // Limpiar si hubiese detalles
            $stmtDel = $db->prepare("DELETE FROM toma_inventario_detalle WHERE id_toma = ?");
            $stmtDel->execute([$idToma]);

            // Insumos (agrupados por articulo, ya que es stock por tipo)
            // Calculamos stock disponible sumando todas las entradas y restando despachos (en este caso lo que diga ii)
            $sqlInsumos = "
                SELECT ii.id_articulo, SUM(ii.cantidad_ii) as stock
                FROM inventario_insumos ii
                WHERE ii.eliminado = false
                GROUP BY ii.id_articulo
            ";
            $stmtIns = $db->query($sqlInsumos);
            $insumos = $stmtIns->fetchAll(PDO::FETCH_ASSOC);

            $stmtInsert = $db->prepare("
                INSERT INTO toma_inventario_detalle (id_toma, tipo, id_articulo, cantidad_sistema, cantidad_fisica, diferencia)
                VALUES (?, 'INSUMO', ?, ?, ?, ?)
            ");
            
            foreach ($insumos as $ins) {
                $stock = (int)$ins['stock'];
                if ($stock > 0) {
                    $stmtInsert->execute([$idToma, $ins['id_articulo'], $stock, $stock, 0]);
                }
            }

            // Bienes (conteo físico de activos)
            $sqlBienes = "
                SELECT ib.id_articulo, COUNT(*) as stock
                FROM inventario_bienes ib
                WHERE ib.eliminado = false AND ib.id_estado_bienes IN (1, 2, 3) -- Operativo, Regular, Dañado
                GROUP BY ib.id_articulo
            ";
            $stmtB = $db->query($sqlBienes);
            $bienes = $stmtB->fetchAll(PDO::FETCH_ASSOC);

            $stmtInsertBien = $db->prepare("
                INSERT INTO toma_inventario_detalle (id_toma, tipo, id_articulo, cantidad_sistema, cantidad_fisica, diferencia)
                VALUES (?, 'BIEN', ?, ?, ?, ?)
            ");

            foreach ($bienes as $bien) {
                $stock = (int)$bien['stock'];
                if ($stock > 0) {
                    $stmtInsertBien->execute([$idToma, $bien['id_articulo'], $stock, $stock, 0]);
                }
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function actualizarConteo(int $idDetalle, int $cantidadFisica, string $justificacion): void
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("
            UPDATE toma_inventario_detalle 
            SET cantidad_fisica = :fisica, 
                diferencia = :fisica - cantidad_sistema, 
                justificacion = :just
            WHERE id_detalle = :id
        ");
        $stmt->execute([
            ':fisica' => $cantidadFisica,
            ':just' => $justificacion,
            ':id' => $idDetalle
        ]);
    }
}
