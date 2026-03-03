<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\MovimientoPresupuestario;
use PDO;

class MovimientoPresupuestarioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'movimiento_presupuestario';
    }

    public function all(string $search = ''): array
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE eliminado = 0";
        // Si hay search, se podría cruzar con tablas foráneas o filtrar por operación
        $sql .= " ORDER BY id_movimiento_presupuestario DESC";
        
        $stmt = $this->getPdo()->query($sql);
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapRowToEntity($row);
        }
        return $results;
    }

    public function findByComprobanteId(int $id_comprobante): array
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM {$this->getTable()} WHERE id_comprobante = :id AND eliminado = 0");
        $stmt->execute(['id' => $id_comprobante]);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapRowToEntity($row);
        }
        return $results;
    }

    public function getCuentasAperturablesPorEstructura(int $id_estruc): array
    {
        // Obtiene todas las cuentas del plan unico que esten asociadas a presupuestos pre-cargados
        // pero que AUN NO tengan un movimiento 'AAP' (Apertura) en esta estructura.
        // Simulando Form_APERTURAR_CUENTAS__BuscarCuentasPorAperturar de SIGAFS

        $sql = "
            SELECT p.id_codigo_plan_unico, p.codigo_plan_unico, p.denominacion
            FROM plan_unico_cuentas p
            WHERE p.eliminado = 0
            AND p.id_codigo_plan_unico NOT IN (
                SELECT m.id_codigo_plan_unico 
                FROM movimiento_presupuestario m
                JOIN comprobante_presupuestario c ON m.id_comprobante = c.id_comprobante
                WHERE m.id_estruc_presupuestaria = :id_estruc 
                AND m.id_operacion = 'AAP'
                AND m.eliminado = 0
                AND c.eliminado = 0
            )
            ORDER BY p.codigo_plan_unico ASC
        ";

        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['id_estruc' => $id_estruc]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(MovimientoPresupuestario $m): int
    {
        if ($m->id_movimiento_presupuestario) {
            $stmt = $this->getPdo()->prepare("UPDATE {$this->getTable()} SET 
                id_comprobante = :id_c,
                id_estruc_presupuestaria = :id_ep,
                id_codigo_plan_unico = :id_cpu,
                id_operacion = :op,
                monto_mp = :monto,
                descripcion_mp = :desc
                WHERE id_movimiento_presupuestario = :id");
            $stmt->execute([
                'id_c' => $m->id_comprobante,
                'id_ep' => $m->id_estruc_presupuestaria,
                'id_cpu' => $m->id_codigo_plan_unico,
                'op' => $m->id_operacion,
                'monto' => $m->monto_mp,
                'desc' => $m->descripcion_mp,
                'id' => $m->id_movimiento_presupuestario
            ]);
            return $m->id_movimiento_presupuestario;
        } else {
            $stmt = $this->getPdo()->prepare("INSERT INTO {$this->getTable()} (
                id_comprobante, id_estruc_presupuestaria, id_codigo_plan_unico, 
                id_operacion, monto_mp, descripcion_mp
            ) VALUES (
                :id_c, :id_ep, :id_cpu, :op, :monto, :desc
            )");
            $stmt->execute([
                'id_c' => $m->id_comprobante,
                'id_ep' => $m->id_estruc_presupuestaria,
                'id_cpu' => $m->id_codigo_plan_unico,
                'op' => $m->id_operacion,
                'monto' => $m->monto_mp,
                'desc' => $m->descripcion_mp
            ]);
            return (int)$this->getPdo()->lastInsertId();
        }
    }

    /** Elimina lógicamente todas las líneas de un comprobante (para edición) */
    public function deleteByComprobanteId(int $id_comprobante): void
    {
        $stmt = $this->getPdo()->prepare("UPDATE {$this->getTable()} SET eliminado = 1 WHERE id_comprobante = :id");
        $stmt->execute(['id' => $id_comprobante]);
    }

    /**
     * Calcula el disponible presupuestario de una cuenta/estructura:
     * suma los montos de operaciones que AUMENTAN (AAP, CA)
     * menos los que REDUCEN (CG, TR)
     */
    public function getDisponible(int $id_estruc, int $id_cuenta): float
    {
        $sql = "
            SELECT 
                COALESCE(SUM(CASE WHEN m.id_operacion IN ('AAP','CA') THEN m.monto_mp ELSE 0 END), 0)
                - COALESCE(SUM(CASE WHEN m.id_operacion IN ('CG','TR') THEN m.monto_mp ELSE 0 END), 0)
                AS disponible
            FROM movimiento_presupuestario m
            JOIN comprobante_presupuestario c ON m.id_comprobante = c.id_comprobante
            WHERE m.id_estruc_presupuestaria = :id_ep
              AND m.id_codigo_plan_unico = :id_cpu
              AND m.eliminado = 0
              AND c.eliminado = 0
        ";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['id_ep' => $id_estruc, 'id_cpu' => $id_cuenta]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($row['disponible'] ?? 0);
    }

    /**
     * Devuelve un resumen completo de disponibilidad presupuestaria por estructura y cuenta.
     * Incluye: asignado_inicial (AAP), creditos (CA), gastos (CG), traspasos_salida (TR), disponible.
     * Filtros opcionales: id_estruc, id_cuenta.
     */
    public function getResumenDisponibilidad(?int $id_estruc = null, ?int $id_cuenta = null): array
    {
        $where = "m.eliminado = 0 AND c.eliminado = 0";
        $params = [];

        if ($id_estruc !== null) {
            $where .= " AND m.id_estruc_presupuestaria = :id_ep";
            $params['id_ep'] = $id_estruc;
        }
        if ($id_cuenta !== null) {
            $where .= " AND m.id_codigo_plan_unico = :id_cpu";
            $params['id_cpu'] = $id_cuenta;
        }

        $sql = "
            SELECT
                m.id_estruc_presupuestaria,
                ep.descripcion_ep,
                m.id_codigo_plan_unico,
                puc.codigo_plan_unico,
                puc.denominacion AS denominacion_cuenta,
                COALESCE(SUM(CASE WHEN m.id_operacion = 'AAP' THEN m.monto_mp ELSE 0 END), 0) AS asignado_inicial,
                COALESCE(SUM(CASE WHEN m.id_operacion = 'CA'  THEN m.monto_mp ELSE 0 END), 0) AS creditos_adicionales,
                COALESCE(SUM(CASE WHEN m.id_operacion = 'CG'  THEN m.monto_mp ELSE 0 END), 0) AS gastos_causados,
                COALESCE(SUM(CASE WHEN m.id_operacion = 'TR'  THEN m.monto_mp ELSE 0 END), 0) AS traspasos_reduccion,
                COALESCE(SUM(CASE WHEN m.id_operacion IN ('AAP','CA') THEN m.monto_mp ELSE 0 END), 0)
                - COALESCE(SUM(CASE WHEN m.id_operacion IN ('CG','TR') THEN m.monto_mp ELSE 0 END), 0) AS disponible
            FROM movimiento_presupuestario m
            JOIN comprobante_presupuestario c   ON m.id_comprobante = c.id_comprobante
            JOIN estruc_presupuestaria ep        ON m.id_estruc_presupuestaria = ep.id_estruc_presupuestaria
            JOIN plan_unico_cuentas puc          ON m.id_codigo_plan_unico = puc.id_codigo_plan_unico
            WHERE $where
            GROUP BY m.id_estruc_presupuestaria, m.id_codigo_plan_unico,
                     ep.descripcion_ep, puc.codigo_plan_unico, puc.denominacion
            ORDER BY ep.descripcion_ep ASC, puc.codigo_plan_unico ASC
        ";

        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve los movimientos agrupados por EP y Cuenta para el Mayor Analítico.
     * Calcula el saldo acumulado (running balance) por cada fila de movimiento.
     */
    public function getMayorAnalitico(?int $id_estruc = null, ?int $id_cuenta = null, ?string $anio = null): array
    {
        $where  = "m.eliminado = 0 AND c.eliminado = 0";
        $params = [];

        if ($id_estruc !== null) {
            $where .= " AND m.id_estruc_presupuestaria = :id_ep";
            $params['id_ep'] = $id_estruc;
        }
        if ($id_cuenta !== null) {
            $where .= " AND m.id_codigo_plan_unico = :id_cpu";
            $params['id_cpu'] = $id_cuenta;
        }
        if ($anio !== null) {
            $where .= " AND strftime('%Y', c.fecha_c) = :anio";
            $params['anio'] = $anio;
        }

        // Trae todos los movimientos ordenados por EP, cuenta y fecha
        $sql = "
            SELECT
                m.id_estruc_presupuestaria,
                ep.descripcion_ep,
                m.id_codigo_plan_unico,
                puc.codigo_plan_unico,
                puc.denominacion AS denominacion_cuenta,
                m.id_operacion,
                m.monto_mp,
                c.numero_c,
                c.fecha_c,
                c.denominacion_c,
                m.descripcion_mp
            FROM movimiento_presupuestario m
            JOIN comprobante_presupuestario c   ON m.id_comprobante = c.id_comprobante
            JOIN estruc_presupuestaria ep        ON m.id_estruc_presupuestaria = ep.id_estruc_presupuestaria
            JOIN plan_unico_cuentas puc          ON m.id_codigo_plan_unico = puc.id_codigo_plan_unico
            WHERE $where
            ORDER BY ep.descripcion_ep ASC, puc.codigo_plan_unico ASC, c.fecha_c ASC, m.id_movimiento_presupuestario ASC
        ";

        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por EP + Cuenta y calcular saldo acumulado por fila
        $grupos = [];
        foreach ($rows as $row) {
            $keyEp  = $row['id_estruc_presupuestaria'];
            $keyCpu = $row['id_codigo_plan_unico'];
            $key    = "$keyEp|$keyCpu";

            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'id_estruc_presupuestaria' => $keyEp,
                    'descripcion_ep'           => $row['descripcion_ep'],
                    'id_codigo_plan_unico'     => $keyCpu,
                    'codigo_plan_unico'        => $row['codigo_plan_unico'],
                    'denominacion_cuenta'      => $row['denominacion_cuenta'],
                    'asignado_inicial'         => 0.0,
                    'creditos_adicionales'     => 0.0,
                    'gastos_causados'          => 0.0,
                    'traspasos_reduccion'      => 0.0,
                    'disponible'               => 0.0,
                    'movimientos'              => [],
                ];
            }

            // Actualizar saldo acumulado (running balance)
            $op = $row['id_operacion'];
            $monto = (float)$row['monto_mp'];
            if (in_array($op, ['AAP', 'CA'])) {
                $grupos[$key]['disponible'] += $monto;
            } else {
                $grupos[$key]['disponible'] -= $monto;
            }

            // Acumuladores de totales por tipo
            match($op) {
                'AAP' => $grupos[$key]['asignado_inicial']     += $monto,
                'CA'  => $grupos[$key]['creditos_adicionales'] += $monto,
                'CG'  => $grupos[$key]['gastos_causados']      += $monto,
                'TR'  => $grupos[$key]['traspasos_reduccion']  += $monto,
                default => null,
            };

            $grupos[$key]['movimientos'][] = array_merge($row, [
                'saldo_acumulado' => $grupos[$key]['disponible'],
            ]);
        }

        return array_values($grupos);
    }

    public function delete(int $id): void
    {
        $stmt = $this->getPdo()->prepare("UPDATE {$this->getTable()} SET eliminado = 1 WHERE id_movimiento_presupuestario = :id");
        $stmt->execute(['id' => $id]);
    }

    private function mapRowToEntity(array $row): MovimientoPresupuestario
    {
        return new MovimientoPresupuestario(
            (int)$row['id_comprobante'],
            (int)$row['id_estruc_presupuestaria'],
            (int)$row['id_codigo_plan_unico'],
            $row['id_operacion'],
            (float)$row['monto_mp'],
            $row['descripcion_mp'],
            (int)$row['id_movimiento_presupuestario']
        );
    }
}
