<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\MovimientoBancario;
use PDO;

class MovimientoBancarioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'movimiento_bancario';
    }

    /**
     * @return array
     */
    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                MB.id_movimiento_bancario, MB.id_cta_bancaria, MB.id_tipo_operacion_bancaria, 
                MB.monto, MB.fecha, MB.referencia, MB.conciliado, MB.fecha_conciliacion,
                CB.numero_cta_bancaria, B.nombre_banco,
                TOB.nombre_tipo_operacion_bancaria, TOB.acronimo_tipo_operacion_bancaria
            FROM movimiento_bancario AS MB
            JOIN cta_bancaria AS CB ON MB.id_cta_bancaria = CB.id_cta_bancaria
            JOIN banco AS B ON CB.id_banco = B.id_banco
            JOIN tipo_operacion_bancaria AS TOB ON MB.id_tipo_operacion_bancaria = TOB.id_tipo_operacion_bancaria
            WHERE MB.eliminado = false
        ";

        if ($search !== '') {
            $sql .= " AND (
                MB.referencia LIKE :search
                OR CB.numero_cta_bancaria LIKE :search
                OR B.nombre_banco LIKE :search
            )";
        }
        $sql .= " ORDER BY MB.fecha DESC, MB.id_movimiento_bancario DESC";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $movimiento = new MovimientoBancario(
                (int)$row['id_cta_bancaria'],
                (int)$row['id_tipo_operacion_bancaria'],
                (float)($row['monto'] ?? 0),
                $row['fecha'] ?? '',
                $row['referencia'] ?? '',
                (bool)($row['conciliado'] ?? false),
                $row['fecha_conciliacion'] ?? null,
                (int)$row['id_movimiento_bancario']
            );
            $results[] = [
                'entity' => $movimiento,
                'cuenta_bancaria' => $row['numero_cta_bancaria'],
                'banco' => $row['nombre_banco'],
                'operacion_nombre' => $row['nombre_tipo_operacion_bancaria'],
                'operacion_acronimo' => $row['acronimo_tipo_operacion_bancaria'],
            ];
        }

        return $results;
    }

    public function find(int $id): ?MovimientoBancario
    {
        $row = $this->query()->where('id_movimiento_bancario', '=', $id)->where('eliminado', '=', 'false')->first();
        if (!$row) {
            return null;
        }

        return new MovimientoBancario(
            (int)$row['id_cta_bancaria'],
            (int)$row['id_tipo_operacion_bancaria'],
            (float)$row['monto'],
            $row['fecha'],
            $row['referencia'],
            (bool)$row['conciliado'],
            $row['fecha_conciliacion'],
            (int)$row['id_movimiento_bancario']
        );
    }

    public function save(MovimientoBancario $item): bool
    {
        $data = [
            'id_cta_bancaria' => $item->idCuenta,
            'id_tipo_operacion_bancaria' => $item->idTipoOperacion,
            'monto' => $item->monto,
            'fecha' => $item->fecha,
            'referencia' => $item->referencia,
            'conciliado' => $item->conciliado ? 'true' : 'false',
            'fecha_conciliacion' => $item->fechaConciliacion,
        ];

        if ($item->id) {
            return $this->query()->where('id_movimiento_bancario', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        return (bool)$id;
    }

    public function getTiposOperacion(): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM tipo_operacion_bancaria WHERE eliminado = false");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByAccount(int $idCta, string $desde, string $hasta): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                MB.*,
                TOB.nombre_tipo_operacion_bancaria, TOB.acronimo_tipo_operacion_bancaria
            FROM movimiento_bancario AS MB
            JOIN tipo_operacion_bancaria AS TOB ON MB.id_tipo_operacion_bancaria = TOB.id_tipo_operacion_bancaria
            WHERE MB.id_cta_bancaria = ? AND MB.fecha BETWEEN ? AND ? AND MB.eliminado = false
            ORDER BY MB.fecha ASC, MB.id_movimiento_bancario ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idCta, $desde, $hasta]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSaldoAnterior(int $idCta, string $desde): float
    {
        $db = $this->getPdo();
        $sql = "SELECT SUM(monto) FROM movimiento_bancario WHERE id_cta_bancaria = ? AND fecha < ? AND eliminado = false";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idCta, $desde]);

        return (float)$stmt->fetchColumn();
    }

    public function getMovimientosParaConciliar(int $idCta, string $hasta, ?string $fechaConciliacion = null): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                MB.*,
                TOB.nombre_tipo_operacion_bancaria, TOB.acronimo_tipo_operacion_bancaria
            FROM movimiento_bancario AS MB
            JOIN tipo_operacion_bancaria AS TOB ON MB.id_tipo_operacion_bancaria = TOB.id_tipo_operacion_bancaria
            WHERE MB.id_cta_bancaria = ? AND MB.fecha <= ? 
            AND (MB.conciliado = false" . ($fechaConciliacion ? " OR MB.fecha_conciliacion = ?" : "") . ")
            AND MB.eliminado = false
            ORDER BY MB.fecha ASC, MB.id_movimiento_bancario ASC
        ";
        $stmt = $db->prepare($sql);
        $params = [$idCta, $hasta];
        if ($fechaConciliacion) {
            $params[] = $fechaConciliacion;
        }
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSaldoLibros(int $idCta, string $hasta): float
    {
        $db = $this->getPdo();
        $sql = "SELECT SUM(monto) FROM movimiento_bancario WHERE id_cta_bancaria = ? AND fecha <= ? AND eliminado = false";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idCta, $hasta]);
        
        return (float)$stmt->fetchColumn();
    }

    public function procesarConciliacionMasiva(int $idCta, string $fechaConciliacion, array $idsConciliados): void
    {
        $db = $this->getPdo();
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) $db->beginTransaction();

        try {
            $sqlUncheck = "UPDATE movimiento_bancario SET conciliado = false, fecha_conciliacion = NULL 
                           WHERE id_cta_bancaria = ? AND fecha_conciliacion = ?";
            $paramsUncheck = [$idCta, $fechaConciliacion];
            
            if (!empty($idsConciliados)) {
                $placeholders = implode(',', array_fill(0, count($idsConciliados), '?'));
                $sqlUncheck .= " AND id_movimiento_bancario NOT IN ($placeholders)";
                $paramsUncheck = array_merge($paramsUncheck, $idsConciliados);
            }
            $stmt1 = $db->prepare($sqlUncheck);
            $stmt1->execute($paramsUncheck);

            if (!empty($idsConciliados)) {
                $placeholders2 = implode(',', array_fill(0, count($idsConciliados), '?'));
                $sqlCheck = "UPDATE movimiento_bancario SET conciliado = true, fecha_conciliacion = ? 
                             WHERE id_cta_bancaria = ? AND id_movimiento_bancario IN ($placeholders2)";
                $paramsCheck = array_merge([$fechaConciliacion, $idCta], $idsConciliados);
                $stmt2 = $db->prepare($sqlCheck);
                $stmt2->execute($paramsCheck);
            }

            if (!$inTransaction) $db->commit();
        } catch (\Exception $e) {
            if (!$inTransaction) $db->rollBack();
            throw $e;
        }
    }
}
