<?php

namespace App\Controllers;

class ReportesCxpController extends BaseController
{
    private \App\Repositories\DocumentoRepository $repo;
    private \App\Repositories\ProveedorRepository $proveedorRepo;
    private \App\Repositories\ComprobanteRetencionRepository $retencionRepo;

    public function __construct(
        \App\Repositories\DocumentoRepository $repo,
        \App\Repositories\ProveedorRepository $proveedorRepo,
        \App\Repositories\ComprobanteRetencionRepository $retencionRepo
    ) {
        $this->repo = $repo;
        $this->proveedorRepo = $proveedorRepo;
        $this->retencionRepo = $retencionRepo;
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function pendientes(): void
    {
        $pendientes = $this->repo->getPendientesPago();

        $this->renderView('cxp/reportes/pendientes', [
            'titulo' => 'Cuentas por Pagar - Antigüedad y Pendientes',
            'pendientes' => $pendientes,
        ]);
    }

    public function estadoCuenta(): void
    {
        $idProveedor = $_GET['id_proveedor'] ?? null;
        $proveadoresRaw = $this->proveedorRepo->all();
        $proveadores = [];
        foreach ($proveadoresRaw as $p) {
            $ent = $p['entity'];
            $proveadores[] = [
                'id_proveedor' => $ent->id,
                'rif_proveedor' => $ent->rif,
                'compania_proveedor' => $ent->compania,
            ];
        }

        $movimientos = [];
        $proveedorSeleccionado = null;
        $saldoTotal = 0;

        if ($idProveedor) {
            $proveedorEnt = $this->proveedorRepo->findById((int)$idProveedor);
            if ($proveedorEnt) {
                $proveedorSeleccionado = [
                    'rif_proveedor' => $proveedorEnt->rif,
                    'compania_proveedor' => $proveedorEnt->compania,
                ];
            }

            $rows = $this->repo->getMovimientosProveedor((int)$idProveedor);

            foreach ($rows as $row) {
                // Si la solicitud_pago está contabilizada, el saldo es 0 (ya pagado).
                $pagado = ($row['contabilizada'] == 1) ? $row['monto_pagar_solicitud_pago'] : 0;
                $estado = ($row['contabilizada'] == 1) ? 'PAGADO' : 'PENDIENTE';

                if ($estado === 'PENDIENTE') {
                    $saldoTotal += $row['monto_total_d'];
                }

                $movimientos[] = [
                    'fecha' => $row['fecha_emision_d'],
                    'tipo' => $row['denominacion_tipo_documento'],
                    'nro' => $row['nro_documento_d'],
                    'concepto' => $row['observacion_d'],
                    'monto_facturado' => $row['monto_total_d'],
                    'pagado' => $pagado,
                    'estado' => $estado,
                ];
            }
        }

        $this->renderView('cxp/reportes/estado_cuenta', [
            'titulo' => 'Estado de Cuenta de Proveedor',
            'proveedores' => $proveadores,
            'movimientos' => $movimientos,
            'proveedorSeleccionado' => $proveedorSeleccionado,
            'idProveedor' => $idProveedor,
            'saldoTotal' => $saldoTotal,
        ]);
    }

    public function libroIva(): void
    {
        $mes = $_GET['mes'] ?? date('m');
        $anio = $_GET['anio'] ?? date('Y');
        $asPdf = (bool)($_GET['pdf'] ?? false);

        try {
            $registros = $this->repo->getLibroIva($mes, $anio);
        } catch (\Exception $e) {
            $registros = [];
            $error = "Error al generar libro de IVA: " . $e->getMessage();
        }

        if ($asPdf && !empty($registros)) {
            $pdf = new \App\Services\PdfService('L', 'mm', 'A4'); // Horizontal para el libro
            $pdf->AliasNbPages();
            $pdf->setTitulo("LIBRO DE COMPRAS (IVA) - PERIODO $mes/$anio");
            $pdf->AddPage();

            $cabecera = ['Fecha', 'RIF', 'Proveedor', 'Factura', 'Total', 'Base', 'IVA', 'Retencion'];
            $filas = [];
            foreach ($registros as $r) {
                $filas[] = [
                    date('d/m/Y', strtotime($r['fecha_factura'])),
                    $r['rif_proveedor'],
                    substr($r['compania_proveedor'], 0, 30),
                    $r['numero_factura'],
                    number_format($r['monto_total'], 2, ',', '.'),
                    number_format($r['monto_base'], 2, ',', '.'),
                    number_format($r['monto_impuesto'], 2, ',', '.'),
                    number_format($r['iva_retenido'] ?? 0, 2, ',', '.'),
                ];
            }
            $pdf->TablaElegante($cabecera, $filas);
            $pdf->Output('I', "Libro_IVA_{$mes}_{$anio}.pdf");

            return;
        }

        $this->renderView('cxp/reportes/libro_iva', [
            'titulo' => "Libro de IVA Compras - Período $mes/$anio",
            'registros' => $registros,
            'mes' => $mes,
            'anio' => $anio,
            'error' => $error ?? null,
        ]);
    }

    public function saldosProveedores(): void
    {
        $asPdf = (bool)($_GET['pdf'] ?? false);
        $saldos = $this->repo->getSaldosPendientesPorProveedor();

        if ($asPdf && !empty($saldos)) {
            $pdf = new \App\Services\PdfService();
            $pdf->AliasNbPages();
            $pdf->setTitulo("CONSULTA DE SALDOS PENDIENTES POR PROVEEDOR");
            $pdf->AddPage();

            $cabecera = ['RIF', 'Proveedor', 'Facturas', 'Total Pendiente'];
            $filas = [];
            $totalGeneral = 0;
            foreach ($saldos as $s) {
                $filas[] = [
                    $s['rif_proveedor'],
                    substr($s['compania_proveedor'], 0, 50),
                    $s['cantidad_facturas'],
                    number_format($s['total_deuda'], 2, ',', '.'),
                ];
                $totalGeneral += $s['total_deuda'];
            }
            $pdf->TablaElegante($cabecera, $filas);
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 10, "TOTAL GENERAL PENDIENTE: " . number_format($totalGeneral, 2, ',', '.') . " Bs.", 0, 1, 'R');

            $pdf->Output('I', "Saldos_Proveedores.pdf");

            return;
        }

        $this->renderView('cxp/reportes/saldos_proveedores', [
            'titulo' => 'Cuentas por Pagar: Saldos por Proveedor',
            'saldos' => $saldos,
        ]);
    }

    public function listadoRetenciones(): void
    {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $tipo = $_GET['tipo'] ?? null;
        if ($tipo === 'TODOS') {
            $tipo = null;
        }

        $asPdf = (bool)($_GET['pdf'] ?? false);
        $retenciones = $this->retencionRepo->getListadoRetenciones($desde, $hasta, $tipo);

        if ($asPdf && !empty($retenciones)) {
            $pdf = new \App\Services\PdfService('L', 'mm', 'A4');
            $pdf->AliasNbPages();
            $pdf->setTitulo("LISTADO MAESTRO DE RETENCIONES (" . ($tipo ?: 'IVA e ISLR') . ")");
            $pdf->AddPage();

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 10, "Periodo: " . date('d/m/Y', strtotime($desde)) . " al " . date('d/m/Y', strtotime($hasta)), 0, 1, 'C');
            $pdf->Ln(5);

            $cabecera = ['Fecha', 'Comprobante', 'RIF', 'Proveedor', 'Factura', 'Base', '%', 'Retenido'];
            $filas = [];
            $totalRetenido = 0;
            foreach ($retenciones as $r) {
                $filas[] = [
                    date('d/m/Y', strtotime($r['fecha_emision'])),
                    $r['numero_comprobante'],
                    $r['rif_proveedor'],
                    substr($r['compania_proveedor'], 0, 30),
                    $r['numero_factura'],
                    number_format($r['monto_base'], 2, ',', '.'),
                    number_format($r['porcentaje'], 1) . '%',
                    number_format($r['monto_retenido'], 2, ',', '.'),
                ];
                $totalRetenido += $r['monto_retenido'];
            }
            $pdf->TablaElegante($cabecera, $filas);
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 10, "TOTAL RETENIDO EN PERIODO: " . number_format($totalRetenido, 2, ',', '.') . " Bs.", 0, 1, 'R');

            $pdf->Output('I', "Listado_Retenciones.pdf");

            return;
        }

        $this->renderView('cxp/reportes/listado_retenciones', [
            'titulo' => 'Listado Maestro de Retenciones',
            'retenciones' => $retenciones,
            'desde' => $desde,
            'hasta' => $hasta,
            'tipo' => $tipo ?: 'TODOS',
        ]);
    }
}
