<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\FondoAvance;
use App\Models\FondoAvanceReposicion;
use App\Models\FondoAvanceGasto;
use Exception;
use PDO;

class FondoAvanceRepository extends Repository
{
    protected function getTable(): string
    {
        return 'fondo_avance';
    }

    public function all(string $search = '', int $limit = 100, int $offset = 0): array
    {
        $db = $this->getPdo();
        $where = "WHERE eliminado = false";
        $params = [];

        if ($search !== '') {
            $where .= " AND (denominacion ILIKE :search OR responsable_cedula ILIKE :search)";
            $params[':search'] = "%$search%";
        }

        $sql = "SELECT * FROM fondo_avance $where ORDER BY id_fondo DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?FondoAvance
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM fondo_avance WHERE id_fondo = ? AND eliminado = false");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new FondoAvance(
            $row['denominacion'],
            (float)$row['monto_maximo'],
            $row['responsable_cedula'],
            $row['fecha_creacion'],
            $row['estado'],
            $row['id_cuenta_contable'] ? (int)$row['id_cuenta_contable'] : null,
            (int)$row['id_fondo']
        );
    }

    public function save(FondoAvance $item): int
    {
        $db = $this->getPdo();
        
        if ($item->idFondo) {
            $stmt = $db->prepare("
                UPDATE fondo_avance SET 
                    denominacion = :den,
                    monto_maximo = :monto,
                    responsable_cedula = :cedula,
                    estado = :estado,
                    id_cuenta_contable = :cuenta
                WHERE id_fondo = :id
            ");
            $stmt->execute([
                ':den' => $item->denominacion,
                ':monto' => $item->montoMaximo,
                ':cedula' => $item->responsableCedula,
                ':estado' => $item->estado,
                ':cuenta' => $item->idCuentaContable,
                ':id' => $item->idFondo
            ]);
            return $item->idFondo;
        }

        $stmt = $db->prepare("
            INSERT INTO fondo_avance (denominacion, monto_maximo, responsable_cedula, fecha_creacion, estado, id_cuenta_contable)
            VALUES (:den, :monto, :cedula, :fecha, :estado, :cuenta)
        ");
        $stmt->execute([
            ':den' => $item->denominacion,
            ':monto' => $item->montoMaximo,
            ':cedula' => $item->responsableCedula,
            ':fecha' => $item->fechaCreacion,
            ':estado' => $item->estado,
            ':cuenta' => $item->idCuentaContable
        ]);
        
        return (int)$db->lastInsertId();
    }

    // --- REPOSICIONES ---

    public function getReposiciones(int $idFondo): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("
            SELECT r.*, s.numero_solicitud 
            FROM fondo_avance_reposicion r
            LEFT JOIN solicitud_pago s ON r.id_solicitud_pago = s.id_solicitud_pago
            WHERE r.id_fondo = ? AND r.eliminado = false
            ORDER BY r.id_reposicion DESC
        ");
        $stmt->execute([$idFondo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findReposicionById(int $id): ?FondoAvanceReposicion
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM fondo_avance_reposicion WHERE id_reposicion = ? AND eliminado = false");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new FondoAvanceReposicion(
            (int)$row['id_fondo'],
            $row['fecha_reposicion'],
            (float)$row['monto_rendido'],
            $row['estado'],
            $row['id_solicitud_pago'] ? (int)$row['id_solicitud_pago'] : null,
            (int)$row['id_reposicion']
        );
    }

    public function saveReposicion(FondoAvanceReposicion $item): int
    {
        $db = $this->getPdo();
        
        if ($item->idReposicion) {
            $stmt = $db->prepare("
                UPDATE fondo_avance_reposicion SET 
                    fecha_reposicion = :fecha,
                    monto_rendido = :monto,
                    estado = :estado,
                    id_solicitud_pago = :sol
                WHERE id_reposicion = :id
            ");
            $stmt->execute([
                ':fecha' => $item->fechaReposicion,
                ':monto' => $item->montoRendido,
                ':estado' => $item->estado,
                ':sol' => $item->idSolicitudPago,
                ':id' => $item->idReposicion
            ]);
            return $item->idReposicion;
        }

        $stmt = $db->prepare("
            INSERT INTO fondo_avance_reposicion (id_fondo, fecha_reposicion, monto_rendido, estado, id_solicitud_pago)
            VALUES (:fondo, :fecha, :monto, :estado, :sol)
        ");
        $stmt->execute([
            ':fondo' => $item->idFondo,
            ':fecha' => $item->fechaReposicion,
            ':monto' => $item->montoRendido,
            ':estado' => $item->estado,
            ':sol' => $item->idSolicitudPago
        ]);
        
        return (int)$db->lastInsertId();
    }

    // --- GASTOS ---

    public function getGastos(int $idReposicion): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM fondo_avance_gasto WHERE id_reposicion = ? AND eliminado = false ORDER BY id_gasto ASC");
        $stmt->execute([$idReposicion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveGasto(FondoAvanceGasto $item): int
    {
        $db = $this->getPdo();
        
        if ($item->idGasto) {
            $stmt = $db->prepare("
                UPDATE fondo_avance_gasto SET 
                    fecha_gasto = :fecha,
                    concepto = :concepto,
                    monto = :monto,
                    factura_num = :factura,
                    proveedor_rif = :prov
                WHERE id_gasto = :id
            ");
            $stmt->execute([
                ':fecha' => $item->fechaGasto,
                ':concepto' => $item->concepto,
                ':monto' => $item->monto,
                ':factura' => $item->facturaNum,
                ':prov' => $item->proveedorRif,
                ':id' => $item->idGasto
            ]);
            $this->actualizarTotalReposicion($item->idReposicion);
            return $item->idGasto;
        }

        $stmt = $db->prepare("
            INSERT INTO fondo_avance_gasto (id_reposicion, fecha_gasto, concepto, monto, factura_num, proveedor_rif)
            VALUES (:rep, :fecha, :concepto, :monto, :factura, :prov)
        ");
        $stmt->execute([
            ':rep' => $item->idReposicion,
            ':fecha' => $item->fechaGasto,
            ':concepto' => $item->concepto,
            ':monto' => $item->monto,
            ':factura' => $item->facturaNum,
            ':prov' => $item->proveedorRif
        ]);
        
        $id = (int)$db->lastInsertId();
        $this->actualizarTotalReposicion($item->idReposicion);
        return $id;
    }

    public function deleteGasto(int $idGasto): void
    {
        $db = $this->getPdo();
        $stmtSel = $db->prepare("SELECT id_reposicion FROM fondo_avance_gasto WHERE id_gasto = ?");
        $stmtSel->execute([$idGasto]);
        $idRep = (int)$stmtSel->fetchColumn();

        if ($idRep) {
            $stmt = $db->prepare("UPDATE fondo_avance_gasto SET eliminado = true WHERE id_gasto = ?");
            $stmt->execute([$idGasto]);
            $this->actualizarTotalReposicion($idRep);
        }
    }

    private function actualizarTotalReposicion(int $idReposicion): void
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("
            UPDATE fondo_avance_reposicion 
            SET monto_rendido = (
                SELECT COALESCE(SUM(monto), 0) 
                FROM fondo_avance_gasto 
                WHERE id_reposicion = :id AND eliminado = false
            )
            WHERE id_reposicion = :id
        ");
        $stmt->execute([':id' => $idReposicion]);
    }
}
