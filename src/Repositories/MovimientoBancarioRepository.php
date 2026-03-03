<?php

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
                MB.monto, MB.fecha, MB.referencia,
                CB.numero_cta_bancaria, B.nombre_banco,
                TOB.nombre_tipo_operacion_bancaria, TOB.acronimo_tipo_operacion_bancaria
            FROM movimiento_bancario AS MB
            JOIN cta_bancaria AS CB ON MB.id_cta_bancaria = CB.id_cta_bancaria
            JOIN banco AS B ON CB.id_banco = B.id_banco
            JOIN tipo_operacion_bancaria AS TOB ON MB.id_tipo_operacion_bancaria = TOB.id_tipo_operacion_bancaria
            WHERE MB.eliminado = 0
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
        $row = $this->query()->where('id_movimiento_bancario', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return new MovimientoBancario(
            (int)$row['id_cta_bancaria'],
            (int)$row['id_tipo_operacion_bancaria'],
            (float)$row['monto'],
            $row['fecha'],
            $row['referencia'],
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
        ];

        if ($item->id) {
            return $this->query()->where('id_movimiento_bancario', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        if ($id) {
            $item->id = (int)$id;

            return true;
        }

        return false;
    }

    public function getTiposOperacion(): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM tipo_operacion_bancaria WHERE eliminado = 0");
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
            WHERE MB.id_cta_bancaria = ? AND MB.fecha BETWEEN ? AND ? AND MB.eliminado = 0
            ORDER BY MB.fecha ASC, MB.id_movimiento_bancario ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idCta, $desde, $hasta]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSaldoAnterior(int $idCta, string $desde): float
    {
        $db = $this->getPdo();
        $sql = "SELECT SUM(monto) FROM movimiento_bancario WHERE id_cta_bancaria = ? AND fecha < ? AND eliminado = 0";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idCta, $desde]);

        return (float)$stmt->fetchColumn();
    }
}
