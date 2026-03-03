<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Services\PdfService;

class DocumentalController extends BaseController
{
    public function __construct()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function ordenCompra(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            die("ID de Orden requerido.");
        }

        $db = Connection::getInstance();

        // Datos cabecera
        $stmt = $db->prepare("
            SELECT oc.*, p.compania_proveedor as denominacion_p, p.rif_proveedor as rif_p 
            FROM orden_de_compra oc 
            JOIN proveedor p ON oc.id_proveedor = p.id_proveedor 
            WHERE oc.id_orden_de_compra = ?
        ");
        $stmt->execute([$id]);
        $orden = $stmt->fetch();
        if (!$orden) {
            die("Orden no encontrada.");
        }

        // Datos cuerpo
        $stmtDet = $db->prepare("
            SELECT a.denominacion_a as articulo, aodc.cantidad_aodc as cantidad, aodc.costo_aodc as precio
            FROM articulo_orden_de_compra aodc
            JOIN articulo a ON aodc.id_articulo = a.id_articulo
            WHERE aodc.id_orden_de_compra = ?
        ");
        $stmtDet->execute([$id]);
        $detalles = $stmtDet->fetchAll();

        // Armar PDF
        $pdf = new PdfService();
        $pdf->AliasNbPages();
        $pdf->setTitulo("ORDEN DE COMPRA / SERVICIO NRO: OC-". str_pad($id, 4, '0', STR_PAD_LEFT));
        $pdf->AddPage();

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(100, 6, mb_convert_encoding('Proveedor: ' . $orden['denominacion_p'], 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(90, 6, mb_convert_encoding('Fecha Emisión: ' . $orden['fecha_odc'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $pdf->Cell(100, 6, mb_convert_encoding('RIF: ' . $orden['rif_p'], 'ISO-8859-1', 'UTF-8'), 0, 1);
        $pdf->Cell(100, 6, mb_convert_encoding('Concepto: ' . substr($orden['concepto_odc'], 0, 100), 'ISO-8859-1', 'UTF-8'), 0, 1);

        $pdf->Ln(10);

        // Tabla
        $cabecera = ['Cant.', 'Descripción del Bien/Servicio', 'Precio Unit. (Bs)', 'Subtotal (Bs)'];
        $filasTabla = [];
        $granTotal = 0;

        foreach ($detalles as $d) {
            $subt = $d['cantidad'] * $d['precio'];
            $granTotal += $subt;
            $filasTabla[] = [
                $d['cantidad'],
                $d['articulo'],
                number_format($d['precio'], 2, ',', '.'),
                number_format($subt, 2, ',', '.'),
            ];
        }

        $pdf->TablaElegante($cabecera, $filasTabla);

        // Totales
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(130, 8, 'TOTAL ESTIMADO DE LA ORDEN:', 0, 0, 'R');
        $pdf->Cell(60, 8, 'Bs ' . number_format($granTotal, 2, ',', '.'), 1, 1, 'R');

        // Render FPDF to stdout
        $pdf->Output('I', "Orden_Compra_$id.pdf");
    }

    public function solicitudPago(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            die("ID de Solicitud requerido.");
        }

        $db = Connection::getInstance();

        $stmt = $db->prepare("
            SELECT * FROM solicitud_pago WHERE id_solicitud_pago = ?
        ");
        $stmt->execute([$id]);
        $sol = $stmt->fetch();
        if (!$sol) {
            die("Solicitud no encontrada.");
        }

        $pdf = new PdfService();
        $pdf->AliasNbPages();
        $pdf->setTitulo("SOLICITUD DE PAGO / COMPROBANTE NRO: SP-". str_pad($id, 4, '0', STR_PAD_LEFT));
        $pdf->AddPage();

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(100, 8, mb_convert_encoding('Fecha de Solicitud: ' . $sol['fecha_solicitud_pago'], 'ISO-8859-1', 'UTF-8'));
        $estadoLabel = empty($sol['contabilizada']) ? 'Pendiente / En Proceso' : 'Aprobada / Contabilizada';
        $pdf->Cell(90, 8, mb_convert_encoding('Estado: ' . $estadoLabel, 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'CONCEPTO / DESCRIPCION:', 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->MultiCell(0, 6, mb_convert_encoding($sol['concepto_solicitud_pago'], 'ISO-8859-1', 'UTF-8'));

        $pdf->Ln(15);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'MONTO TOTAL A PAGAR: Bs ' . number_format($sol['monto_pagar_solicitud_pago'], 2, ',', '.'), 1, 1, 'C');

        $pdf->Output('I', "Solicitud_Pago_$id.pdf");
    }

    public function requisicionBienes(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            die("ID de Requisición requerido.");
        }

        $db = Connection::getInstance();

        $stmt = $db->prepare("
            SELECT rb.*, ep.descripcion_ep as denominacion_ep 
            FROM requisicion_bienes rb
            LEFT JOIN estruc_presupuestaria ep ON rb.id_estructura_presupuestaria = ep.id_estruc_presupuestaria
            WHERE rb.id_requisicion_bienes = ?
        ");
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        if (!$req) {
            die("Requisición no encontrada.");
        }

        $stmtDet = $db->prepare("
            SELECT a.denominacion_a as articulo, arb.cantidad_arb as cantidad
            FROM articulo_requisicion_bienes arb
            JOIN articulo a ON arb.id_articulo = a.id_articulo
            WHERE arb.id_requisicion_bienes = ?
        ");
        $stmtDet->execute([$id]);
        $detalles = $stmtDet->fetchAll();

        $pdf = new PdfService();
        $pdf->AliasNbPages();
        $pdf->setTitulo("REQUISICION DE BIENES Y SERVICIOS NRO: RB-". str_pad($id, 4, '0', STR_PAD_LEFT));
        $pdf->AddPage();

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(100, 6, mb_convert_encoding('Estructura Presupuestaria: ' . $req['denominacion_ep'], 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(90, 6, mb_convert_encoding('Fecha: ' . $req['fecha_rb'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $pdf->Cell(100, 6, mb_convert_encoding('Concepto: ' . substr($req['concepto_rb'], 0, 100), 'ISO-8859-1', 'UTF-8'), 0, 1);

        $pdf->Ln(10);

        $cabecera = ['Cant. Solicitada', 'Descripción del Bien/Servicio'];
        $filasTabla = [];

        foreach ($detalles as $d) {
            $filasTabla[] = [
                $d['cantidad'],
                $d['articulo'],
            ];
        }

        $pdf->TablaElegante($cabecera, $filasTabla);
        $pdf->Output('I', "Requisicion_Bienes_$id.pdf");
    }

    public function comprobanteDiario(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            die("ID de Comprobante requerido.");
        }

        $db = Connection::getInstance();

        $stmt = $db->prepare("SELECT * FROM comprobante_diario WHERE id_comprobante_diario = ?");
        $stmt->execute([$id]);
        $cd = $stmt->fetch();
        if (!$cd) {
            die("Comprobante Diario no encontrado.");
        }

        $stmtDet = $db->prepare("
            SELECT m.*, c.codigo_cuenta, c.denominacion_cuenta 
            FROM movimiento_contable m
            JOIN cuenta_contable c ON m.id_cuenta_contable = c.id_cuenta_contable
            WHERE m.id_comprobante_diario = ?
        ");
        $stmtDet->execute([$id]);
        $movimientos = $stmtDet->fetchAll();

        $pdf = new PdfService();
        $pdf->AliasNbPages();
        $pdf->setTitulo("COMPROBANTE DE DIARIO NRO: ". $cd['numero_comprobante']);
        $pdf->AddPage();

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(100, 6, mb_convert_encoding('Fecha del Asiento: ' . $cd['fecha_comprobante'], 'ISO-8859-1', 'UTF-8'));
        $pdf->Ln(6);
        $pdf->Cell(0, 6, mb_convert_encoding('Concepto: ' . $cd['concepto'], 'ISO-8859-1', 'UTF-8'), 0, 1);

        $pdf->Ln(10);

        $cabecera = ['Código Cuenta', 'Denominación', 'Debe (Bs)', 'Haber (Bs)'];
        $filasTabla = [];
        $totalDebe = 0;
        $totalHaber = 0;

        foreach ($movimientos as $m) {
            $deje = $m['tipo_operacion_mc'] === 'D' ? $m['monto_mc'] : 0;
            $haber = $m['tipo_operacion_mc'] === 'H' ? $m['monto_mc'] : 0;
            $totalDebe += $deje;
            $totalHaber += $haber;

            $filasTabla[] = [
                $m['codigo_cuenta'],
                $m['denominacion_cuenta'],
                $deje > 0 ? number_format($deje, 2, ',', '.') : '',
                $haber > 0 ? number_format($haber, 2, ',', '.') : '',
            ];
        }

        $pdf->TablaElegante($cabecera, $filasTabla);

        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);

        $ancho = $pdf->GetPageWidth() - 20;
        $pdf->Cell($ancho / 2, 8, 'TOTALES:', 0, 0, 'R');
        $pdf->Cell($ancho / 4, 8, 'Bs ' . number_format($totalDebe, 2, ',', '.'), 1, 0, 'R');
        $pdf->Cell($ancho / 4, 8, 'Bs ' . number_format($totalHaber, 2, ',', '.'), 1, 1, 'R');

        $pdf->Output('I', "Comprobante_Diario_".$cd['numero_comprobante'].".pdf");
    }

    public function documentoCxP(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            die("ID de Documento requerido.");
        }

        $db = Connection::getInstance();

        $stmt = $db->prepare("
            SELECT d.*, p.compania_proveedor, p.rif_proveedor, td.denominacion_tipo_documento 
            FROM documento d
            JOIN proveedor p ON d.id_proveedor = p.id_proveedor
            JOIN tipo_documento td ON d.id_tipo_documento = td.id_tipo_documento
            WHERE d.id_documento = ?
        ");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        if (!$doc) {
            die("Documento no encontrado.");
        }

        $pdf = new PdfService();
        $pdf->AliasNbPages();
        $pdf->setTitulo("RECEPCION DE DOCUMENTO (CxP) NRO: ". $doc['nro_documento_d']);
        $pdf->AddPage();

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(100, 6, mb_convert_encoding('PROVEEDOR: ' . $doc['compania_proveedor'], 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(90, 6, mb_convert_encoding('Fecha Emisión: ' . $doc['fecha_emision_d'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $pdf->Cell(100, 6, mb_convert_encoding('RIF: ' . $doc['rif_proveedor'], 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(90, 6, mb_convert_encoding('Vencimiento: ' . $doc['fecha_vencimiento_d'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $pdf->Cell(100, 6, mb_convert_encoding('TIPO DOCUMENTO: ' . $doc['denominacion_tipo_documento'], 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(90, 6, mb_convert_encoding('Nro. Control: ' . $doc['nro_control_d'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');

        $pdf->Ln(10);
        $pdf->Cell(0, 6, mb_convert_encoding('Concepto / Observaciones: ' . $doc['observacion_d'], 'ISO-8859-1', 'UTF-8'), 0, 1);
        $pdf->Ln(10);

        $cabecera = ['Base Imponible (Bs)', 'Impuestos (Bs)', 'Monto Total Facturado (Bs)'];
        $filasTabla = [[
            number_format($doc['monto_base_d'], 2, ',', '.'),
            number_format($doc['monto_impuesto_d'], 2, ',', '.'),
            number_format($doc['monto_total_d'], 2, ',', '.'),
        ]];

        $pdf->TablaElegante($cabecera, $filasTabla);
        $pdf->Output('I', "Documento_CxP_".$doc['id_documento'].".pdf");
    }

    public function ordenServicio(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            die("ID de Orden de Servicio requerido.");
        }

        $db = Connection::getInstance();

        $stmt = $db->prepare("
            SELECT os.*, p.compania_proveedor as denominacion_p, p.rif_proveedor as rif_p 
            FROM orden_de_servicio os 
            JOIN proveedor p ON os.id_proveedor = p.id_proveedor 
            WHERE os.id_orden_de_servicio = ?
        ");
        $stmt->execute([$id]);
        $orden = $stmt->fetch();
        if (!$orden) {
            die("Orden de Servicio no encontrada.");
        }

        $stmtDet = $db->prepare("
            SELECT s.denominacion as servicio, sods.cantidad_sods as cantidad, sods.costo_sods as precio
            FROM servicio_orden_de_servicio sods
            JOIN servicio s ON sods.id_servicio = s.id_servicio
            WHERE sods.id_orden_de_servicio = ?
        ");
        $stmtDet->execute([$id]);
        $detalles = $stmtDet->fetchAll();

        $pdf = new PdfService();
        $pdf->AliasNbPages();
        $pdf->setTitulo("ORDEN DE SERVICIO NRO: OS-". str_pad($id, 4, '0', STR_PAD_LEFT));
        $pdf->AddPage();

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(100, 6, mb_convert_encoding('Proveedor: ' . $orden['denominacion_p'], 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(90, 6, mb_convert_encoding('Fecha Emisión: ' . $orden['fecha_os'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $pdf->Cell(100, 6, mb_convert_encoding('RIF: ' . $orden['rif_p'], 'ISO-8859-1', 'UTF-8'), 0, 1);
        $pdf->Cell(100, 6, mb_convert_encoding('Concepto: ' . substr($orden['concepto_os'], 0, 100), 'ISO-8859-1', 'UTF-8'), 0, 1);

        $pdf->Ln(10);

        $cabecera = ['Cant.', 'Descripción del Servicio', 'Precio Unit. (Bs)', 'Subtotal (Bs)'];
        $filasTabla = [];
        $granTotal = 0;

        foreach ($detalles as $d) {
            $subt = $d['cantidad'] * $d['precio'];
            $granTotal += $subt;
            $filasTabla[] = [
                $d['cantidad'],
                $d['servicio'],
                number_format($d['precio'], 2, ',', '.'),
                number_format($subt, 2, ',', '.'),
            ];
        }

        $pdf->TablaElegante($cabecera, $filasTabla);

        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);

        // IVA line if present
        if ($orden['monto_iva_os'] > 0) {
            $pdf->Cell(130, 8, 'BASE IMPONIBLE:', 0, 0, 'R');
            $pdf->Cell(60, 8, 'Bs ' . number_format($granTotal, 2, ',', '.'), 1, 1, 'R');
            $pdf->Cell(130, 8, 'IVA (' . $orden['porcentaje_iva_os'] . '%):', 0, 0, 'R');
            $pdf->Cell(60, 8, 'Bs ' . number_format($orden['monto_iva_os'], 2, ',', '.'), 1, 1, 'R');
        }

        $pdf->Cell(130, 8, 'TOTAL ESTIMADO DE LA ORDEN:', 0, 0, 'R');
        $pdf->Cell(60, 8, 'Bs ' . number_format($orden['monto_total_os'], 2, ',', '.'), 1, 1, 'R');

        $pdf->Output('I', "Orden_Servicio_$id.pdf");
    }

    public function requisicionServicios(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            die("ID de Requisición requerido.");
        }

        $db = Connection::getInstance();

        $stmt = $db->prepare("
            SELECT rs.*, ep.descripcion_ep as denominacion_ep 
            FROM requisicion_servicios rs
            LEFT JOIN estruc_presupuestaria ep ON rs.id_estructura_presupuestaria = ep.id_estruc_presupuestaria
            WHERE rs.id_requisicion_servicios = ?
        ");
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        if (!$req) {
            die("Requisición de Servicios no encontrada.");
        }

        $stmtDet = $db->prepare("
            SELECT s.denominacion as servicio, srs.cantidad_srs as cantidad
            FROM servicio_requisicion_servicios srs
            JOIN servicio s ON srs.id_servicio = s.id_servicio
            WHERE srs.id_requisicion_servicios = ?
        ");
        $stmtDet->execute([$id]);
        $detalles = $stmtDet->fetchAll();

        $pdf = new PdfService();
        $pdf->AliasNbPages();
        $pdf->setTitulo("REQUISICION DE SERVICIOS NRO: RS-". str_pad($id, 4, '0', STR_PAD_LEFT));
        $pdf->AddPage();

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(100, 6, mb_convert_encoding('Estructura Presupuestaria: ' . $req['denominacion_ep'], 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(90, 6, mb_convert_encoding('Fecha: ' . $req['fecha_rs'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $pdf->Cell(100, 6, mb_convert_encoding('Concepto: ' . substr($req['concepto_rs'], 0, 100), 'ISO-8859-1', 'UTF-8'), 0, 1);

        $pdf->Ln(10);

        $cabecera = ['Cant. Solicitada', 'Descripción del Servicio'];
        $filasTabla = [];

        foreach ($detalles as $d) {
            $filasTabla[] = [
                $d['cantidad'],
                $d['servicio'],
            ];
        }

        $pdf->TablaElegante($cabecera, $filasTabla);
        $pdf->Output('I', "Requisicion_Servicios_$id.pdf");
    }

    public function comprobanteRetencion(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            die("ID de Comprobante requerido.");
        }

        $db = Connection::getInstance();

        $stmt = $db->prepare("
            SELECT 
                CR.*,
                F.numero_factura, F.fecha_factura, F.monto_base, F.monto_impuesto, F.monto_total,
                P.compania_proveedor, P.rif_proveedor, P.direccion_proveedor
            FROM comprobante_retencion CR
            JOIN factura F ON CR.id_factura = F.id_factura
            JOIN proveedor P ON F.id_proveedor = P.id_proveedor
            WHERE CR.id_comprobante_retencion = ?
        ");
        $stmt->execute([$id]);
        $comp = $stmt->fetch();
        if (!$comp) {
            die("Comprobante de Retención no encontrado.");
        }

        $pdf = new PdfService();
        $pdf->AliasNbPages();
        $pdf->setTitulo("COMPROBANTE DE RETENCION DE " . $comp['tipo_retencion'] . " NRO: " . $comp['numero_comprobante']);
        $pdf->AddPage();

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(100, 6, mb_convert_encoding('Agente de Retención: SISTEMA ADMINISTRATIVO SADI', 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(90, 6, mb_convert_encoding('Fecha Emisión: ' . $comp['fecha_emision'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 6, mb_convert_encoding('DATOS DEL SUJETO RETENIDO', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(130, 6, mb_convert_encoding('Razón Social / Nombre: ' . $comp['compania_proveedor'], 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(60, 6, mb_convert_encoding('RIF: ' . $comp['rif_proveedor'], 'ISO-8859-1', 'UTF-8'), 0, 1);
        $pdf->Cell(0, 6, mb_convert_encoding('Dirección Fiscal: ' . ($comp['direccion_proveedor'] ?? 'NO REGISTRADA en el Sistema'), 'ISO-8859-1', 'UTF-8'), 0, 1);
        $pdf->Ln(10);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 6, mb_convert_encoding('DATOS DE LA FACTURA ASOCIADA', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $pdf->SetFont('Arial', '', 11);

        $cabecera = ['Nro Factura', 'Fecha Factura', 'Base Imponible', 'Impuesto / IVA', '% Ret.', 'Monto Retenido'];

        $porcentaje = number_format($comp['porcentaje'], 2, ',', '.') . '%';
        $montoRet = number_format($comp['monto_retenido'], 2, ',', '.');
        $base = number_format($comp['monto_base'], 2, ',', '.');
        $imp = number_format($comp['monto_impuesto'], 2, ',', '.');

        $filasTabla = [[
            $comp['numero_factura'],
            $comp['fecha_factura'],
            $base,
            $imp,
            $porcentaje,
            $montoRet,
        ]];

        $pdf->TablaElegante($cabecera, $filasTabla);

        $pdf->Ln(30);
        $pdf->Cell(0, 6, '_____________________________________', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Firma y Sello Agente de Retencion', 0, 1, 'C');

        $pdf->Output('I', "Comprobante_{$comp['tipo_retencion']}_{$comp['numero_comprobante']}.pdf");
    }
}
