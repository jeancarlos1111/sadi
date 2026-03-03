<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\ComprobantePresupuestario;
use PDO;

class ComprobantePresupuestarioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'comprobante_presupuestario';
    }

    public function all(string $search = ''): array
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE eliminado = 0";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (numero_c LIKE :s OR denominacion_c LIKE :s)";
            $params['s'] = "%$search%";
        }

        $sql .= " ORDER BY id_comprobante DESC";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new ComprobantePresupuestario(
                $row['acronimo_c'],
                $row['numero_c'],
                $row['fecha_c'],
                $row['denominacion_c'],
                $row['referencia_c'],
                $row['beneficiario_cedula'],
                $row['estado'],
                (int)$row['id_comprobante']
            );
        }
        return $results;
    }

    public function findById(int $id): ?ComprobantePresupuestario
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM {$this->getTable()} WHERE id_comprobante = :id AND eliminado = 0");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new ComprobantePresupuestario(
            $row['acronimo_c'],
            $row['numero_c'],
            $row['fecha_c'],
            $row['denominacion_c'],
            $row['referencia_c'],
            $row['beneficiario_cedula'],
            $row['estado'],
            (int)$row['id_comprobante']
        );
    }

    /**
     * Devuelve todos los comprobantes excepto los de un determinado tipo de acrónimo
     * Útil para excluir los de Apertura (AAP) del listado de Comprobantes de Gasto
     */
    public function allExcept(array $excluded = [], string $search = '', string $tipo = ''): array
    {
        $placeholders = implode(',', array_fill(0, count($excluded), '?'));
        $sql = "SELECT * FROM {$this->getTable()} WHERE eliminado = 0";
        $params = [];

        if (!empty($excluded)) {
            $sql .= " AND acronimo_c NOT IN ($placeholders)";
            $params = array_values($excluded);
        }
        if (!empty($tipo)) {
            $sql .= ' AND acronimo_c = ?';
            $params[] = $tipo;
        }
        if (!empty($search)) {
            $sql .= ' AND (numero_c LIKE ? OR denominacion_c LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= ' ORDER BY id_comprobante DESC';
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new ComprobantePresupuestario(
                $row['acronimo_c'],
                $row['numero_c'],
                $row['fecha_c'],
                $row['denominacion_c'],
                $row['referencia_c'],
                $row['beneficiario_cedula'],
                $row['estado'],
                (int)$row['id_comprobante']
            );
        }
        return $results;
    }

    public function getNextId(): int
    {
         $stmt = $this->getPdo()->query("SELECT seq FROM sqlite_sequence WHERE name='comprobante_presupuestario'");
         $row = $stmt->fetch(PDO::FETCH_ASSOC);
         return $row ? (int)$row['seq'] + 1 : 1;
    }

    public function save(ComprobantePresupuestario $c): int
    {
        if ($c->id_comprobante) {
            $stmt = $this->getPdo()->prepare("UPDATE {$this->getTable()} SET 
                acronimo_c = :acronimo,
                numero_c = :numero,
                fecha_c = :fecha,
                denominacion_c = :denominacion,
                referencia_c = :referencia,
                beneficiario_cedula = :beneficiario,
                estado = :estado
                WHERE id_comprobante = :id");
            $stmt->execute([
                'acronimo' => $c->acronimo_c,
                'numero' => $c->numero_c,
                'fecha' => $c->fecha_c,
                'denominacion' => $c->denominacion_c,
                'referencia' => $c->referencia_c,
                'beneficiario' => $c->beneficiario_cedula,
                'estado' => $c->estado,
                'id' => $c->id_comprobante
            ]);
            return $c->id_comprobante;
        } else {
            $stmt = $this->getPdo()->prepare("INSERT INTO {$this->getTable()} (
                acronimo_c, numero_c, fecha_c, denominacion_c, 
                referencia_c, beneficiario_cedula, estado
            ) VALUES (
                :acronimo, :numero, :fecha, :denominacion, 
                :referencia, :beneficiario, :estado
            )");
            $stmt->execute([
                'acronimo' => $c->acronimo_c,
                'numero' => $c->numero_c,
                'fecha' => $c->fecha_c,
                'denominacion' => $c->denominacion_c,
                'referencia' => $c->referencia_c,
                'beneficiario' => $c->beneficiario_cedula,
                'estado' => $c->estado
            ]);
            return (int)$this->getPdo()->lastInsertId();
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->getPdo()->prepare("UPDATE {$this->getTable()} SET eliminado = 1 WHERE id_comprobante = :id");
        $stmt->execute(['id' => $id]);
    }
}
