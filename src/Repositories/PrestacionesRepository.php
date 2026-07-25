<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\PrestacionGarantia;
use PDO;

class PrestacionesRepository extends Repository
{
    protected function getTable(): string
    {
        return 'prestacion_garantia';
    }

    public function find(int $id): ?PrestacionGarantia
    {
        $row = $this->query()->where('id_prestacion', '=', $id)->where('eliminado', '=', 'false')->first();
        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function save(PrestacionGarantia $item): bool
    {
        $data = [
            'cod_ficha' => $item->codFicha,
            'periodo' => $item->periodo,
            'tipo' => $item->tipo,
            'dias_depositados' => $item->diasDepositados,
            'salario_integral_diario' => $item->salarioIntegralDiario,
            'monto' => $item->monto,
            'fecha_proceso' => $item->fechaProceso,
        ];

        if ($item->id) {
            return $this->query()->where('id_prestacion', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        return (bool)$id;
    }

    /**
     * Obtiene el historial de depósitos de un trabajador
     */
    public function getEstadoCuenta(int $codFicha): array
    {
        $sql = "SELECT * FROM prestacion_garantia WHERE cod_ficha = ? AND eliminado = false ORDER BY fecha_proceso ASC, id_prestacion ASC";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$codFicha]);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapRowToEntity($row);
        }
        return $results;
    }

    /**
     * Verifica si un periodo ya fue procesado para evitar duplicidad
     */
    public function existePeriodoProcesado(string $periodo, string $tipo): bool
    {
        $sql = "SELECT 1 FROM prestacion_garantia WHERE periodo = ? AND tipo = ? AND eliminado = false LIMIT 1";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$periodo, $tipo]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Inserta masivamente el cálculo de un trimestre
     */
    public function procesarMasivo(array $prestaciones): void
    {
        if (empty($prestaciones)) return;
        
        $db = $this->getPdo();
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) $db->beginTransaction();

        try {
            $sql = "INSERT INTO prestacion_garantia (cod_ficha, periodo, tipo, dias_depositados, salario_integral_diario, monto, fecha_proceso) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            
            foreach ($prestaciones as $p) {
                $stmt->execute([
                    $p->codFicha,
                    $p->periodo,
                    $p->tipo,
                    $p->diasDepositados,
                    $p->salarioIntegralDiario,
                    $p->monto,
                    $p->fechaProceso
                ]);
            }

            if (!$inTransaction) $db->commit();
        } catch (\Exception $e) {
            if (!$inTransaction) $db->rollBack();
            throw $e;
        }
    }

    private function mapRowToEntity(array $row): PrestacionGarantia
    {
        return new PrestacionGarantia(
            (int)$row['id_prestacion'],
            (int)$row['cod_ficha'],
            $row['periodo'],
            $row['tipo'],
            (int)$row['dias_depositados'],
            (float)$row['salario_integral_diario'],
            (float)$row['monto'],
            $row['fecha_proceso'],
            (bool)$row['eliminado']
        );
    }
}
