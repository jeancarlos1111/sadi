<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\ComprobanteRetencion;
use PDO;

class ComprobanteRetencionRepository extends Repository
{
    protected function getTable(): string
    {
        return 'comprobante_retencion';
    }

    /**
     * @return array
     */
    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                CR.id_comprobante_retencion, CR.id_factura, CR.tipo_retencion,
                CR.numero_comprobante, CR.porcentaje, CR.monto_retenido, CR.fecha_emision,
                F.numero_factura, F.fecha_factura, F.monto_total,
                P.compania_proveedor AS proveedor_nombre, P.rif_proveedor AS proveedor_rif
            FROM comprobante_retencion AS CR
            JOIN factura AS F ON CR.id_factura = F.id_factura
            JOIN proveedor AS P ON F.id_proveedor = P.id_proveedor
            WHERE CR.eliminado = false
        ";

        if ($search !== '') {
            $sql .= " AND (
                CR.numero_comprobante LIKE :search
                OR CR.tipo_retencion LIKE :search
                OR F.numero_factura LIKE :search
                OR P.compania_proveedor LIKE :search
                OR P.rif_proveedor LIKE :search
            )";
        }
        $sql .= " ORDER BY CR.fecha_emision DESC, CR.numero_comprobante DESC";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $comprobante = new ComprobanteRetencion(
                (int)$row['id_factura'],
                $row['tipo_retencion'],
                $row['numero_comprobante'],
                (float)($row['porcentaje'] ?? 0),
                (float)($row['monto_retenido'] ?? 0),
                $row['fecha_emision'] ?? '',
                (int)$row['id_comprobante_retencion']
            );
            $results[] = [
                'entity' => $comprobante,
                'factura_str' => $row['numero_factura'] . ' (' . $row['fecha_factura'] . ')',
                'factura_monto' => $row['monto_total'],
                'proveedor' => $row['proveedor_rif'] . ' - ' . $row['proveedor_nombre'],
            ];
        }

        return $results;
    }

    public function findById(int $id): ?ComprobanteRetencion
    {
        $row = $this->query()->where('id_comprobante_retencion', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return new ComprobanteRetencion(
            (int)$row['id_factura'],
            $row['tipo_retencion'],
            $row['numero_comprobante'],
            (float)$row['porcentaje'],
            (float)$row['monto_retenido'],
            $row['fecha_emision'],
            (int)$row['id_comprobante_retencion']
        );
    }

    /**
     * Obtiene comprobantes por periodo para exportación SENIAT.
     */
    public function getByPeriodo(string $mes, string $anio, string $tipo): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                CR.*,
                F.numero_factura, F.fecha_factura, F.monto_base, F.monto_impuesto, F.monto_total,
                d.nro_control_d,
                P.compania_proveedor, P.rif_proveedor
            FROM comprobante_retencion CR
            JOIN factura F ON CR.id_factura = F.id_factura
            JOIN documento d ON F.numero_factura = d.nro_documento_d AND F.id_proveedor = d.id_proveedor
            JOIN proveedor P ON F.id_proveedor = P.id_proveedor
            WHERE CR.eliminado = false
            AND CR.tipo_retencion = :tipo
            AND strftime('%m', CR.fecha_emision) = :mes
            AND strftime('%Y', CR.fecha_emision) = :anio
            ORDER BY CR.fecha_emision ASC, CR.id_comprobante_retencion ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':mes', $mes);
        $stmt->bindValue(':anio', $anio);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un listado maestro de retenciones emitidas en un rango de fechas.
     */
    public function getListadoRetenciones(string $desde, string $hasta, ?string $tipo = null): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                CR.*,
                F.numero_factura, F.fecha_factura, F.monto_base, F.monto_impuesto, F.monto_total,
                P.compania_proveedor, P.rif_proveedor
            FROM comprobante_retencion CR
            JOIN factura F ON CR.id_factura = F.id_factura
            JOIN proveedor P ON F.id_proveedor = P.id_proveedor
            WHERE CR.eliminado = false
            AND CR.fecha_emision BETWEEN :desde AND :hasta
        ";

        if ($tipo) {
            $sql .= " AND CR.tipo_retencion = :tipo";
        }

        $sql .= " ORDER BY CR.fecha_emision ASC, CR.numero_comprobante ASC";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':desde', $desde);
        $stmt->bindValue(':hasta', $hasta);
        if ($tipo) {
            $stmt->bindValue(':tipo', $tipo);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
