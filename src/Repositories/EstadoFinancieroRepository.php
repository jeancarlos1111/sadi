<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class EstadoFinancieroRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Obtiene el Balance de Comprobación
     * Sumatoria de Debe y Haber por cada cuenta de movimiento en un rango de fechas.
     */
    public function getBalanceComprobacion(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "
            SELECT 
                C.codigo_cuenta AS codigo,
                C.denominacion_cuenta AS denominacion,
                COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'D' THEN MC.monto_mc ELSE 0 END), 0) AS total_debe,
                COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'H' THEN MC.monto_mc ELSE 0 END), 0) AS total_haber
            FROM cuenta_contable C
            LEFT JOIN movimiento_contable MC ON C.id_cuenta_contable = MC.id_cuenta_contable AND MC.eliminado = false
            LEFT JOIN comprobante_diario CD ON MC.id_comprobante_diario = CD.id_comprobante_diario AND CD.eliminado = false AND CD.fecha_comprobante BETWEEN :desde AND :hasta
            GROUP BY C.codigo_cuenta, C.denominacion_cuenta
            ORDER BY C.codigo_cuenta ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['desde' => $fechaDesde, 'hasta' => $fechaHasta]);

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agregamos cuentas padre o simplemente listamos las que tuvieron movimiento
        // Para el balance de comprobación suele listarse todas las de movimiento (nivel 6)

        return $resultados;
    }

    /**
     * Obtiene el Estado de Resultados
     * Cuentas de Ingresos (Clase 4) vs Gastos (Clase 5)
     */
    public function getEstadoResultados(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "
            SELECT 
                SUBSTRING(C.codigo_cuenta FROM 1 FOR 1) AS clase,
                C.codigo_cuenta AS codigo,
                C.denominacion_cuenta AS denominacion,
                COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'H' THEN MC.monto_mc ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'D' THEN MC.monto_mc ELSE 0 END), 0) AS saldo_ingreso,
                COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'D' THEN MC.monto_mc ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'H' THEN MC.monto_mc ELSE 0 END), 0) AS saldo_gasto
            FROM cuenta_contable C
            JOIN movimiento_contable MC ON C.id_cuenta_contable = MC.id_cuenta_contable AND MC.eliminado = false
            JOIN comprobante_diario CD ON MC.id_comprobante_diario = CD.id_comprobante_diario
            WHERE CD.eliminado = false 
              AND CD.fecha_comprobante BETWEEN :desde AND :hasta
              AND (C.codigo_cuenta LIKE '4%' OR C.codigo_cuenta LIKE '5%')
            GROUP BY C.codigo_cuenta, C.denominacion_cuenta
            ORDER BY C.codigo_cuenta ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['desde' => $fechaDesde, 'hasta' => $fechaHasta]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el Balance General (Situación Financiera)
     * Activos (Clase 1), Pasivos (Clase 2), Patrimonio (Clase 3)
     */
    public function getBalanceGeneral(string $fechaHasta): array
    {
        $sql = "
            SELECT 
                SUBSTRING(C.codigo_cuenta FROM 1 FOR 1) AS clase,
                C.codigo_cuenta AS codigo,
                C.denominacion_cuenta AS denominacion,
                COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'D' THEN MC.monto_mc ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'H' THEN MC.monto_mc ELSE 0 END), 0) AS saldo_activo,
                COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'H' THEN MC.monto_mc ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'D' THEN MC.monto_mc ELSE 0 END), 0) AS saldo_pasivo_patrimonio
            FROM cuenta_contable C
            LEFT JOIN movimiento_contable MC ON C.id_cuenta_contable = MC.id_cuenta_contable AND MC.eliminado = false
            LEFT JOIN comprobante_diario CD ON MC.id_comprobante_diario = CD.id_comprobante_diario AND CD.eliminado = false AND CD.fecha_comprobante <= :hasta
            WHERE C.codigo_cuenta LIKE '1%' OR C.codigo_cuenta LIKE '2%' OR C.codigo_cuenta LIKE '3%'
            GROUP BY C.codigo_cuenta, C.denominacion_cuenta
            HAVING (COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'D' THEN MC.monto_mc ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'H' THEN MC.monto_mc ELSE 0 END), 0)) <> 0 
               OR (COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'H' THEN MC.monto_mc ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN MC.tipo_operacion_mc = 'D' THEN MC.monto_mc ELSE 0 END), 0)) <> 0
            ORDER BY C.codigo_cuenta ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['hasta' => $fechaHasta]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
