<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\DocumentoPorPagar;
use Exception;
use PDO;

class DocumentoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'documento';
    }

    /**
     * @return array
     */
    public function all(string $search = '', string $mes = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                D.id_documento, D.nro_documento_d, D.nro_control_d, D.fecha_emision_d, D.fecha_vencimiento_d,
                D.id_proveedor, D.id_tipo_documento, D.monto_base_d, D.monto_impuesto_d, D.monto_total_d,
                D.observacion_d, P.compania_proveedor, TD.denominacion_tipo_documento
            FROM documento AS D
            JOIN proveedor AS P ON D.id_proveedor = P.id_proveedor
            JOIN tipo_documento AS TD ON D.id_tipo_documento = TD.id_tipo_documento
            WHERE D.eliminado = false
        ";

        if ($mes !== '' && $mes >= '01' && $mes <= '12') {
            $sql .= " AND to_char(D.fecha_emision_d, 'MM') = :mes";
        }

        if ($search !== '') {
            $sql .= " AND (
                D.nro_documento_d ILIKE :search
                OR D.nro_control_d ILIKE :search
                OR P.compania_proveedor ILIKE :search
                OR D.observacion_d ILIKE :search
                OR CAST(D.id_documento AS TEXT) ILIKE :search
            )";
        }
        $sql .= " ORDER BY D.fecha_emision_d DESC, D.id_documento ASC";

        $stmt = $db->prepare($sql);
        if ($mes !== '' && $mes >= '01' && $mes <= '12') {
            $stmt->bindValue(':mes', $mes);
        }
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $doc = new DocumentoPorPagar(
                $row['nro_documento_d'],
                $row['nro_control_d'] ?? '',
                $row['fecha_emision_d'],
                $row['fecha_vencimiento_d'] ?? '',
                (int)$row['id_proveedor'],
                (int)$row['id_tipo_documento'],
                (float)($row['monto_base_d'] ?? 0),
                (float)($row['monto_impuesto_d'] ?? 0),
                (float)($row['monto_total_d'] ?? 0),
                $row['observacion_d'] ?? '',
                (int)$row['id_documento']
            );
            $results[] = [
                'entity' => $doc,
                'proveedor' => $row['compania_proveedor'],
                'tipo_documento' => $row['denominacion_tipo_documento'],
            ];
        }

        return $results;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_documento', '=', $id)->update(['eliminado' => true]);
    }

    /**
     * Trae las órdenes de compra que tienen Recepción de Almacén (Tipo Documento = 2)
     * y que aún NO han sido facturadas (Tipo Documento = 1).
     */
    public function getOrdenesPendientesFacturar(): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                d.id_orden_de_compra,
                oc.fecha_odc,
                oc.concepto_odc,
                p.compania_proveedor,
                p.rif_proveedor,
                d.monto_total_d,
                d.id_proveedor
            FROM documento d
            JOIN orden_de_compra oc ON d.id_orden_de_compra = oc.id_orden_de_compra
            JOIN proveedor p ON d.id_proveedor = p.id_proveedor
            WHERE d.id_tipo_documento = 2 -- Notas de Entrega enviadas por Almacén
            AND d.eliminado = 0
            AND NOT EXISTS (
                SELECT 1 FROM documento d_fac
                WHERE d_fac.id_orden_de_compra = d.id_orden_de_compra
                AND d_fac.id_tipo_documento = 1 -- Factura ya generada
                AND (d_fac.eliminado = 0 OR d_fac.eliminado IS NULL)
            )
            ORDER BY d.id_orden_de_compra DESC
        ";

        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trae las órdenes de servicio contabilizadas que NO han sido facturadas
     */
    public function getOrdenesServicioPendientesFacturar(): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                os.id_orden_de_servicio,
                os.fecha_os,
                os.concepto_os,
                p.compania_proveedor,
                p.rif_proveedor,
                os.monto_total_os as monto_total_d,
                os.id_proveedor
            FROM orden_de_servicio os
            JOIN proveedor p ON os.id_proveedor = p.id_proveedor
            WHERE os.contabilizada = 1
            AND os.eliminado = 0
            AND NOT EXISTS (
                SELECT 1 FROM documento d_fac
                WHERE d_fac.id_orden_de_servicio = os.id_orden_de_servicio
                AND d_fac.id_tipo_documento = 1 -- Factura ya generada
                AND (d_fac.eliminado = 0 OR d_fac.eliminado IS NULL)
            )
            ORDER BY os.id_orden_de_servicio DESC
        ";

        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDatosNotaEntrega(int $idOrden): ?array
    {
        $sql = "
            SELECT d.*, p.compania_proveedor, p.rif_proveedor, oc.concepto_odc
            FROM documento d
            JOIN proveedor p ON d.id_proveedor = p.id_proveedor
            JOIN orden_de_compra oc ON d.id_orden_de_compra = oc.id_orden_de_compra
            WHERE d.id_orden_de_compra = ? AND d.id_tipo_documento = 2 AND d.eliminado = false
            LIMIT 1
        ";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$idOrden]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getDatosOrdenServicio(int $idOrden): ?array
    {
        $sql = "
            SELECT os.id_orden_de_servicio, os.id_proveedor, os.monto_base_os as monto_base_d, 
                   os.monto_iva_os as monto_impuesto_d, os.monto_total_os as monto_total_d, 
                   os.concepto_os as concepto_odc, p.compania_proveedor, p.rif_proveedor
            FROM orden_de_servicio os
            JOIN proveedor p ON os.id_proveedor = p.id_proveedor
            WHERE os.id_orden_de_servicio = ? AND os.contabilizada = 1 AND os.eliminado = false
            LIMIT 1
        ";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$idOrden]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * TRANSACTION: Crear Factura, Calcular Retenciones y Generar Solicitud de Pago
     * Extraído de FacturacionCxp::registrarFacturaYRetenciones
     */
    public function registrarFacturaYRetenciones(array $data): bool
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            $idOc = !empty($data['id_orden_de_compra']) ? (int)$data['id_orden_de_compra'] : null;
            $idOs = !empty($data['id_orden_de_servicio']) ? (int)$data['id_orden_de_servicio'] : null;
            $idProveedor = (int)$data['id_proveedor'];

            if (!$idOc && !$idOs) {
                throw new Exception("Debe proveer un ID de Orden de Compra o de Servicio.");
            }

            // 1. Verificar que no exista ya la Factura
            if ($idOc) {
                $stmtVerif = $db->prepare("SELECT id_documento FROM documento WHERE id_orden_de_compra = ? AND id_tipo_documento = 1");
                $stmtVerif->execute([$idOc]);
                if ($stmtVerif->fetch()) {
                    throw new Exception("Ya existe una factura registrada para esta Orden de Compra.");
                }
            } else {
                $stmtVerif = $db->prepare("SELECT id_documento FROM documento WHERE id_orden_de_servicio = ? AND id_tipo_documento = 1");
                $stmtVerif->execute([$idOs]);
                if ($stmtVerif->fetch()) {
                    throw new Exception("Ya existe una factura registrada para esta Orden de Servicio.");
                }
            }

            // 2. Insertar FACTURA principal en tabla genérica de CxP Documentos
            $stmtDoc = $db->prepare("
                INSERT INTO documento 
                (id_orden_de_compra, id_orden_de_servicio, nro_documento_d, nro_control_d, fecha_emision_d, fecha_vencimiento_d, id_proveedor, id_tipo_documento, monto_base_d, monto_impuesto_d, monto_total_d, observacion_d)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?) -- Tipo 1 = Factura
            ");

            $montoBase = (float)$data['monto_base_d'];
            $montoIva = (float)$data['monto_impuesto_d'];
            $montoTotal = $montoBase + $montoIva;

            $obsEstandar = $idOc ? "Factura asociada a OC N° " . $idOc : "Factura asociada a OS N° " . $idOs;

            $stmtDoc->execute([
                $idOc,
                $idOs,
                $data['nro_documento_d'],
                $data['nro_control_d'],
                $data['fecha_emision_d'],
                $data['fecha_vencimiento_d'] ?? null,
                $idProveedor,
                $montoBase,
                $montoIva,
                $montoTotal,
                $obsEstandar,
            ]);

            $idDocumentoFactura = $db->lastInsertId();

            // 3. Insertar registro específico en tabla FACTURA de Retenciones
            $stmtFacturaFiscal = $db->prepare("
                INSERT INTO factura
                (id_proveedor, numero_factura, fecha_factura, monto_base, monto_impuesto, monto_total)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtFacturaFiscal->execute([
                $idProveedor,
                $data['nro_documento_d'],
                $data['fecha_emision_d'],
                $montoBase,
                $montoIva,
                $montoTotal,
            ]);
            $idFacturaFiscal = $db->lastInsertId();

            // 4. Calcular y Registrar Retenciones si aplican mediante Patrón Strategy
            $totalRetenido = 0;
            $fechaEmisionRet = date('Y-m-d');

            $estrategiasActivas = [];
            if (isset($data['porcentaje_retencion_iva']) && (float)$data['porcentaje_retencion_iva'] > 0) {
                $estrategiasActivas[] = [
                    'strategy' => new \App\Services\Taxes\RetencionIvaStrategy(),
                    'porcentaje' => (float)$data['porcentaje_retencion_iva'],
                ];
            }
            if (isset($data['porcentaje_retencion_islr']) && (float)$data['porcentaje_retencion_islr'] > 0) {
                $estrategiasActivas[] = [
                    'strategy' => new \App\Services\Taxes\RetencionIslrStrategy(),
                    'porcentaje' => (float)$data['porcentaje_retencion_islr'],
                ];
            }

            foreach ($estrategiasActivas as $item) {
                /** @var \App\Services\Taxes\RetencionStrategy $estrategia */
                $estrategia = $item['strategy'];
                $porcentaje = $item['porcentaje'];

                $montoRet = $estrategia->calcular($montoBase, $porcentaje, $montoIva);
                $tipoStrategy = $estrategia->getTipo();

                $totalRetenido += $montoRet;

                $stmtRet = $db->prepare("
                    INSERT INTO comprobante_retencion
                    (id_factura, tipo_retencion, numero_comprobante, porcentaje, monto_retenido, fecha_emision, codigo_seniat)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                // Código SENIAT por defecto (IVA = 0, ISLR = concepto genérico 001)
                $codigoSeniat = ($tipoStrategy === 'IVA') ? '0' : '001';

                $stmtRet->execute([
                    $idFacturaFiscal,
                    $tipoStrategy,
                    $tipoStrategy . '-' . date('Ym') . '-' . str_pad($idFacturaFiscal, 6, "0", STR_PAD_LEFT),
                    $porcentaje,
                    $montoRet,
                    $fechaEmisionRet,
                    $codigoSeniat,
                ]);
            }

            $montoAlProveedor = $montoTotal - $totalRetenido;

            if ($montoAlProveedor > 0) {
                $stmtPago = $db->prepare("
                    INSERT INTO solicitud_pago
                    (fecha_solicitud_pago, concepto_solicitud_pago, monto_pagar_solicitud_pago, id_documento)
                    VALUES (?, ?, ?, ?)
                ");
                $stmtPago->execute([
                    date('Y-m-d'),
                    'Pago por Factura ' . $data['nro_documento_d'] . ' (' . ($idOc ? "OC #$idOc" : "OS #$idOs") . ')',
                    $montoAlProveedor,
                    $idDocumentoFactura,
                ]);
            }

            /** 6. CAUSADO (SÓLO PARA ÓRDENES DE SERVICIO) */
            if ($idOs) {
                $stmtDetOs = $db->prepare("
                    SELECT s.id_codigo_plan_unico, (sods.cantidad_sods * sods.costo_sods) as monto_renglon, sods.aplica_iva
                    FROM servicio_orden_de_servicio sods
                    JOIN servicio s ON sods.id_servicio = s.id_servicio
                    WHERE sods.id_orden_de_servicio = ?
                ");
                $stmtDetOs->execute([$idOs]);

                $stmtGetIva = $db->prepare("SELECT porcentaje_iva_os FROM orden_de_servicio WHERE id_orden_de_servicio = ?");
                $stmtGetIva->execute([$idOs]);
                $pctIvaOs = (float)$stmtGetIva->fetchColumn();

                $stmtPptoCausado = $db->prepare("UPDATE presupuesto_gastos SET monto_causado = monto_causado + ? WHERE id_codigo_plan_unico = ?");

                foreach ($stmtDetOs->fetchAll(PDO::FETCH_ASSOC) as $item) {
                    $idPartida = $item['id_codigo_plan_unico'];
                    $montoRenglon = (float)$item['monto_renglon'];
                    if ($item['aplica_iva']) {
                        $montoRenglon += ($montoRenglon * ($pctIvaOs / 100));
                    }
                    if ($idPartida) {
                        $stmtPptoCausado->execute([$montoRenglon, $idPartida]);
                    }
                }
            }

            $db->commit();

            return true;

        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }
    /**
     * Obtiene los datos para el Libro de IVA de Compras.
     */
    public function getLibroIva(string $mes, string $anio): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                f.fecha_factura,
                p.rif_proveedor,
                p.compania_proveedor,
                f.numero_factura,
                d.nro_control_d,
                f.monto_total,
                f.monto_base,
                f.monto_impuesto,
                cr.numero_comprobante as nro_retencion,
                cr.monto_retenido as iva_retenido,
                cr.fecha_emision as fecha_retencion,
                cr.porcentaje as pct_retencion
            FROM factura f
            JOIN documento d ON f.numero_factura = d.nro_documento_d 
                 AND f.id_proveedor = d.id_proveedor
            JOIN proveedor p ON f.id_proveedor = p.id_proveedor
            LEFT JOIN comprobante_retencion cr ON f.id_factura = cr.id_factura 
                 AND cr.tipo_retencion = 'IVA' AND cr.eliminado = false
            WHERE d.eliminado = false
            AND strftime('%m', f.fecha_factura) = :mes
            AND strftime('%Y', f.fecha_factura) = :anio
            ORDER BY f.fecha_factura ASC, f.id_factura ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':mes', $mes);
        $stmt->bindValue(':anio', $anio);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene facturas pendientes de pago (solicitud_pago no contabilizada o nula).
     */
    public function getPendientesPago(): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT p.rif_proveedor, p.compania_proveedor,
                   d.nro_documento_d, d.fecha_emision_d, d.monto_total_d, d.monto_base_d, d.monto_impuesto_d,
                   td.denominacion_tipo_documento,
                   CASE WHEN sp.contabilizada = 1 THEN 'PAGADO' ELSE 'PENDIENTE' END as estado_pago,
                   sp.id_solicitud_pago, sp.monto_pagar_solicitud_pago, sp.contabilizada
            FROM documento d
            JOIN proveedor p ON d.id_proveedor = p.id_proveedor
            JOIN tipo_documento td ON d.id_tipo_documento = td.id_tipo_documento
            LEFT JOIN solicitud_pago sp ON sp.id_documento = d.id_documento AND sp.eliminado = false
            WHERE d.eliminado = false
            AND (sp.contabilizada IS NULL OR sp.contabilizada = 0)
            ORDER BY d.fecha_emision_d ASC
        ";

        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los movimientos de un proveedor (facturas y pagos).
     */
    public function getMovimientosProveedor(int $idProveedor): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT d.nro_documento_d, d.fecha_emision_d, d.monto_total_d, d.observacion_d,
                   td.denominacion_tipo_documento,
                   sp.contabilizada, sp.monto_pagar_solicitud_pago
            FROM documento d
            JOIN tipo_documento td ON d.id_tipo_documento = td.id_tipo_documento
            LEFT JOIN solicitud_pago sp ON sp.id_documento = d.id_documento AND sp.eliminado = false
            WHERE d.id_proveedor = ? AND d.eliminado = false
            ORDER BY d.fecha_emision_d ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idProveedor]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los saldos pendientes agrupados por proveedor (solo facturas no pagadas).
     */
    public function getSaldosPendientesPorProveedor(): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                P.id_proveedor, P.rif_proveedor, P.compania_proveedor,
                SUM(F.monto_base) as total_base,
                SUM(F.monto_impuesto) as total_iva,
                SUM(F.monto_total) as total_deuda,
                COUNT(F.id_factura) as cantidad_facturas
            FROM factura F
            JOIN proveedor P ON F.id_proveedor = P.id_proveedor
            JOIN solicitud_pago SP ON F.id_factura = SP.id_documento
            WHERE SP.contabilizada = 0 AND SP.eliminado = 0
            GROUP BY P.id_proveedor, P.rif_proveedor, P.compania_proveedor
            ORDER BY total_deuda DESC
        ";

        $stmt = $db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
