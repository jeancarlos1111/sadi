<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\PeriodoPresupuestario;
use PDO;

class PeriodoPresupuestarioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'periodo_presupuestario';
    }

    /**
     * Devuelve los 12 períodos de un año dado.
     * Si un mes no existe en BD, lo retorna como 'ABIERTO' virtual.
     */
    public function getByAnio(int $anio): array
    {
        $stmt = $this->getPdo()->prepare(
            "SELECT * FROM {$this->getTable()} WHERE anio = :anio ORDER BY mes ASC"
        );
        $stmt->execute(['anio' => $anio]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Indexar por mes
        $existentes = [];
        foreach ($rows as $row) {
            $existentes[(int)$row['mes']] = $this->mapRowToEntity($row);
        }

        // Completar los 12 meses, creando virtuales los que falten
        $periodos = [];
        for ($m = 1; $m <= 12; $m++) {
            $periodos[$m] = $existentes[$m] ?? new PeriodoPresupuestario($anio, $m);
        }

        return $periodos;
    }

    /** Obtiene o crea el período de un mes/año específico */
    public function findOrCreate(int $anio, int $mes): PeriodoPresupuestario
    {
        $stmt = $this->getPdo()->prepare(
            "SELECT * FROM {$this->getTable()} WHERE anio = :anio AND mes = :mes LIMIT 1"
        );
        $stmt->execute(['anio' => $anio, 'mes' => $mes]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToEntity($row) : new PeriodoPresupuestario($anio, $mes);
    }

    public function isMesAbierto(int $anio, int $mes): bool
    {
        $periodo = $this->findOrCreate($anio, $mes);
        return $periodo->isAbierto();
    }

    /**
     * Actualiza el estado de una lista de meses para un año dado.
     * $estados: array[mes => 'ABIERTO'|'CERRADO']
     */
    public function actualizarEstados(int $anio, array $estados, ?string $observacion = null): void
    {
        $pdo = $this->getPdo();

        foreach ($estados as $mes => $estado) {
            $mes   = (int)$mes;
            $fecha_cierre = ($estado === 'CERRADO') ? date('Y-m-d') : null;

            // UPSERT (INSERT OR REPLACE en SQLite)
            $stmt = $pdo->prepare("
                INSERT INTO {$this->getTable()} (anio, mes, estado, fecha_cierre, observacion)
                VALUES (:anio, :mes, :estado, :fecha_cierre, :obs)
                ON CONFLICT(anio, mes) DO UPDATE SET
                    estado       = excluded.estado,
                    fecha_cierre = CASE WHEN excluded.estado = 'CERRADO' THEN excluded.fecha_cierre ELSE NULL END,
                    observacion  = excluded.observacion
            ");
            $stmt->execute([
                'anio'        => $anio,
                'mes'         => $mes,
                'estado'      => $estado,
                'fecha_cierre'=> $fecha_cierre,
                'obs'         => $observacion,
            ]);
        }
    }

    private function mapRowToEntity(array $row): PeriodoPresupuestario
    {
        return new PeriodoPresupuestario(
            (int)$row['anio'],
            (int)$row['mes'],
            $row['estado'],
            $row['fecha_cierre'] ?? null,
            $row['observacion']  ?? null,
            (int)$row['id_periodo']
        );
    }
}
