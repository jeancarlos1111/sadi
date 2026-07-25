<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\AsientoContable;
use Exception;
use PDO;

class AsientoContableRepository extends Repository
{
    protected function getTable(): string
    {
        return 'comprobante_diario';
    }

    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                CD.id_comprobante_diario, CD.numero_comprobante, CD.fecha_comprobante, CD.concepto,
                COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'D' THEN MC.monto_mc ELSE 0 END), 0) AS total_debe,
                COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'H' THEN MC.monto_mc ELSE 0 END), 0) AS total_haber
            FROM comprobante_diario AS CD
            LEFT JOIN movimiento_contable AS MC ON CD.id_comprobante_diario = MC.id_comprobante_diario
            WHERE CD.eliminado = false
        ";

        if ($search !== '') {
            $sql .= " AND (CD.numero_comprobante LIKE :search OR CD.concepto LIKE :search)";
        }

        $sql .= " GROUP BY CD.id_comprobante_diario, CD.numero_comprobante, CD.fecha_comprobante, CD.concepto";
        $sql .= " ORDER BY CD.fecha_comprobante DESC, CD.numero_comprobante DESC";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new AsientoContable(
                $row['numero_comprobante'],
                $row['fecha_comprobante'],
                $row['concepto'],
                (float)$row['total_debe'],
                (float)$row['total_haber'],
                (int)$row['id_comprobante_diario']
            );
        }

        return $results;
    }

    /**
     * Registra un comprobante de diario y sus movimientos de forma programática.
     */
    public function registrarDesdeTransaccion(string $fecha, string $concepto, array $movimientos, ?int $idSolicitudPago = null): int
    {
        $db = $this->getPdo();

        $inTransaction = $db->inTransaction();
        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $totalDebe = 0.0;
            $totalHaber = 0.0;

            foreach ($movimientos as $mov) {
                if ($mov['tipo'] === 'D') {
                    $totalDebe += (float)$mov['monto'];
                } elseif ($mov['tipo'] === 'H') {
                    $totalHaber += (float)$mov['monto'];
                }
            }

            if (round($totalDebe, 2) !== round($totalHaber, 2)) {
                throw new Exception("El asiento contable no cuadra. Debe: {$totalDebe} | Haber: {$totalHaber}");
            }

            $stmtLast = $db->query("SELECT id_comprobante_diario FROM comprobante_diario ORDER BY id_comprobante_diario DESC LIMIT 1");
            $lastId = (int)$stmtLast->fetchColumn();
            $nextId = $lastId + 1;
            $numeroComprobante = 'CD-' . date('Y-m') . '-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);

            $stmtCabecera = $db->prepare("
                INSERT INTO comprobante_diario (numero_comprobante, fecha_comprobante, concepto)
                VALUES (?, ?, ?)
            ");
            $stmtCabecera->execute([$numeroComprobante, $fecha, $concepto]);
            $idComprobante = $db->lastInsertId();

            $stmtMov = $db->prepare("
                INSERT INTO movimiento_contable (id_comprobante_diario, id_cuenta_contable, tipo_operacion_mc, monto_mc)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($movimientos as $mov) {
                if ((float)$mov['monto'] > 0) {
                    $stmtMov->execute([
                        $idComprobante,
                        $mov['id_cuenta'],
                        $mov['tipo'],
                        $mov['monto'],
                    ]);
                }
            }

            if (!$inTransaction) {
                $db->commit();
            }

            return (int)$idComprobante;
        } catch (Exception $e) {
            if (!$inTransaction && $db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public function findById(int $id): ?array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM comprobante_diario WHERE id_comprobante_diario = ? AND eliminado = false");
        $stmt->execute([$id]);
        $cd = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cd) {
            return null;
        }

        $stmtMov = $db->prepare("
            SELECT mc.*, cc.codigo_cuenta, cc.denominacion_cuenta
            FROM movimiento_contable mc
            JOIN cuenta_contable cc ON mc.id_cuenta_contable = cc.id_cuenta_contable
            WHERE mc.id_comprobante_diario = ?
            ORDER BY mc.tipo_operacion_mc DESC, cc.codigo_cuenta ASC
        ");
        $stmtMov->execute([$id]);
        $cd['movimientos'] = $stmtMov->fetchAll(PDO::FETCH_ASSOC);

        return $cd;
    }

    public function anular(int $id, int $idUsuario): void
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("UPDATE comprobante_diario SET eliminado = true WHERE id_comprobante_diario = ?");
        $stmt->execute([$id]);
    }
}
