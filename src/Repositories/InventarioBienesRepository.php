<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\InventarioBien;
use PDO;

class InventarioBienesRepository extends Repository
{
    protected function getTable(): string
    {
        return 'inventario_bienes';
    }

    public function all(string $search = '', int $limit = 100, int $offset = 0): array
    {
        $db = $this->getPdo();
        $where = "WHERE ib.eliminado = false";
        $params = [];

        if ($search !== '') {
            $where .= " AND (a.denominacion_a ILIKE :search OR ib.acronimo_id_ib ILIKE :search)";
            $params[':search'] = "%$search%";
        }

        $sql = "SELECT ib.*, a.denominacion_a 
                FROM inventario_bienes ib
                JOIN articulo a ON ib.id_articulo = a.id_articulo
                $where
                ORDER BY ib.id_inventario_bienes DESC
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

    public function countAll(string $search = ''): int
    {
        $db = $this->getPdo();
        $where = "WHERE ib.eliminado = false";
        $params = [];

        if ($search !== '') {
            $where .= " AND (a.denominacion_a ILIKE :search OR ib.acronimo_id_ib ILIKE :search)";
            $params[':search'] = "%$search%";
        }

        $sql = "SELECT COUNT(*) 
                FROM inventario_bienes ib
                JOIN articulo a ON ib.id_articulo = a.id_articulo
                $where";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?InventarioBien
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM inventario_bienes WHERE id_inventario_bienes = ? AND eliminado = false");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new InventarioBien(
            (int)$row['id_articulo'],
            (int)$row['id_proveedor'],
            $row['fecha_compra_ib'],
            (int)$row['id_orden_de_compra'],
            (float)$row['costo_ib'],
            (int)$row['id_estado_bienes'],
            (int)$row['id_ubicacion_articulo'],
            $row['acronimo_id_ib'],
            (bool)$row['revisado'],
            (int)$row['vida_util_meses'],
            (float)$row['valor_residual'],
            (int)$row['id_inventario_bienes']
        );
    }

    public function save(InventarioBien $item): int
    {
        $db = $this->getPdo();

        if ($item->idInventarioBienes) {
            $stmt = $db->prepare("
                UPDATE inventario_bienes SET 
                    vida_util_meses = :vida,
                    valor_residual = :residual,
                    id_estado_bienes = :estado,
                    id_ubicacion_articulo = :ubicacion
                WHERE id_inventario_bienes = :id
            ");
            $stmt->execute([
                ':vida' => $item->vidaUtilMeses,
                ':residual' => $item->valorResidual,
                ':estado' => $item->idEstadoBienes,
                ':ubicacion' => $item->idUbicacionArticulo,
                ':id' => $item->idInventarioBienes
            ]);
            return $item->idInventarioBienes;
        }

        // The insert is usually handled by RecepcionAlmacenRepository but we can add it here if needed
        $stmt = $db->prepare("
            INSERT INTO inventario_bienes (id_articulo, id_proveedor, fecha_compra_ib, id_orden_de_compra, costo_ib, id_estado_bienes, id_ubicacion_articulo, acronimo_id_ib, revisado, vida_util_meses, valor_residual)
            VALUES (:articulo, :prov, :fecha, :orden, :costo, :estado, :ub, :acronimo, :revisado, :vida, :residual)
        ");
        $stmt->execute([
            ':articulo' => $item->idArticulo,
            ':prov' => $item->idProveedor,
            ':fecha' => $item->fechaCompraIb,
            ':orden' => $item->idOrdenDeCompra,
            ':costo' => $item->costoIb,
            ':estado' => $item->idEstadoBienes,
            ':ub' => $item->idUbicacionArticulo,
            ':acronimo' => $item->acronimoIdIb,
            ':revisado' => $item->revisado ? 1 : 0,
            ':vida' => $item->vidaUtilMeses,
            ':residual' => $item->valorResidual
        ]);

        return (int)$db->lastInsertId();
    }
}
