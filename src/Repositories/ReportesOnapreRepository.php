<?php

namespace App\Repositories;

use App\Database\Repository;
use PDO;

class ReportesOnapreRepository extends Repository
{
    protected function getTable(): string
    {
        return 'cuenta_contable';
    }

    /**
     * Devuelve el Estado de Resultados (Ingresos vs Gastos/Costos) Consolidados
     */
    public function getEstadoResultados(string $anio = '', string $mes = ''): array
    {
        $db = $this->getPdo();

        $filtroFecha = "";
        $params = [];
        if ($anio !== '') {
            $filtroFecha .= " AND strftime('%Y', cd.fecha_comprobante) = ?";
            $params[] = $anio;
        }
        if ($mes !== '') {
            $filtroFecha .= " AND strftime('%m', cd.fecha_comprobante) = ?";
            $params[] = str_pad($mes, 2, '0', STR_PAD_LEFT);
        }

        $sql = "
            SELECT 
                c.tipo_cuenta, 
                c.codigo_cuenta, 
                c.denominacion_cuenta,
                SUM(CASE WHEN m.tipo_operacion_mc = 'D' THEN m.monto_mc ELSE 0 END) as total_debe,
                SUM(CASE WHEN m.tipo_operacion_mc = 'H' THEN m.monto_mc ELSE 0 END) as total_haber,
                CASE 
                    WHEN c.tipo_cuenta = 'INGRESO' THEN 
                        SUM(CASE WHEN m.tipo_operacion_mc = 'H' THEN m.monto_mc ELSE 0 END) - SUM(CASE WHEN m.tipo_operacion_mc = 'D' THEN m.monto_mc ELSE 0 END)
                    WHEN c.tipo_cuenta = 'EGRESO' THEN
                        SUM(CASE WHEN m.tipo_operacion_mc = 'D' THEN m.monto_mc ELSE 0 END) - SUM(CASE WHEN m.tipo_operacion_mc = 'H' THEN m.monto_mc ELSE 0 END)
                    ELSE 0
                END as saldo
            FROM cuenta_contable c
            JOIN movimiento_contable m ON c.id_cuenta_contable = m.id_cuenta_contable
            JOIN comprobante_diario cd ON m.id_comprobante_diario = cd.id_comprobante_diario
            WHERE c.eliminado = 0 
              AND cd.eliminado = 0
              AND c.tipo_cuenta IN ('INGRESO', 'EGRESO')
              {$filtroFecha}
            GROUP BY c.id_cuenta_contable
            ORDER BY c.tipo_cuenta DESC, c.codigo_cuenta ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve el Balance General Consolidado (Activo, Pasivo y Patrimonio)
     */
    public function getBalanceGeneral(string $hastaFecha = ''): array
    {
        $db = $this->getPdo();

        $filtroFecha = "";
        $params = [];
        if ($hastaFecha !== '') {
            $filtroFecha = " AND cd.fecha_comprobante <= ?";
            $params[] = $hastaFecha;
        }

        $sql = "
            SELECT 
                c.tipo_cuenta, 
                c.codigo_cuenta, 
                c.denominacion_cuenta,
                SUM(CASE WHEN m.tipo_operacion_mc = 'D' THEN m.monto_mc ELSE 0 END) as total_debe,
                SUM(CASE WHEN m.tipo_operacion_mc = 'H' THEN m.monto_mc ELSE 0 END) as total_haber,
                CASE 
                    WHEN c.tipo_cuenta = 'ACTIVO' THEN 
                        SUM(CASE WHEN m.tipo_operacion_mc = 'D' THEN m.monto_mc ELSE 0 END) - SUM(CASE WHEN m.tipo_operacion_mc = 'H' THEN m.monto_mc ELSE 0 END)
                    WHEN c.tipo_cuenta IN ('PASIVO', 'PATRIMONIO') THEN
                        SUM(CASE WHEN m.tipo_operacion_mc = 'H' THEN m.monto_mc ELSE 0 END) - SUM(CASE WHEN m.tipo_operacion_mc = 'D' THEN m.monto_mc ELSE 0 END)
                    ELSE 0
                END as saldo
            FROM cuenta_contable c
            JOIN movimiento_contable m ON c.id_cuenta_contable = m.id_cuenta_contable
            JOIN comprobante_diario cd ON m.id_comprobante_diario = cd.id_comprobante_diario
            WHERE c.eliminado = 0 
              AND cd.eliminado = 0
              AND c.tipo_cuenta IN ('ACTIVO', 'PASIVO', 'PATRIMONIO')
              {$filtroFecha}
            GROUP BY c.id_cuenta_contable
            ORDER BY 
                CASE c.tipo_cuenta 
                    WHEN 'ACTIVO' THEN 1
                    WHEN 'PASIVO' THEN 2
                    WHEN 'PATRIMONIO' THEN 3
                    ELSE 4
                END ASC, c.codigo_cuenta ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
