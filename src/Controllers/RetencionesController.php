<?php

namespace App\Controllers;

use App\Repositories\ComprobanteRetencionRepository;
use PDOException;

class RetencionesController extends HomeController
{
    private ComprobanteRetencionRepository $repo;

    public function __construct(ComprobanteRetencionRepository $repo)
    {
        $this->repo = $repo;
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $comprobantes = $this->repo->all($search);
        } catch (PDOException $e) {
            $comprobantes = [];
            $error = "Error al obtener comprobantes de retención: " . $e->getMessage();
        }

        $this->renderView('retenciones/comprobantes/index', [
            'titulo' => 'Comprobantes de Retención',
            'comprobantes' => $comprobantes,
            'search' => $search,
            'error' => $error ?? null,
        ]);
    }

    public function form(): void
    {
        $this->renderView('retenciones/comprobantes/form', [
            'titulo' => 'Emitir Comprobante de Retención',
            'error' => 'La emisión de nuevos comprobantes (IVA, ISLR, 1x1000) a partir del pago de facturas se encuentra en desarrollo.',
        ]);
    }

    public function declarar(): void
    {
        $mes = $_GET['mes'] ?? date('m');
        $anio = $_GET['anio'] ?? date('Y');

        $this->renderView('retenciones/declaracion/index', [
            'titulo' => 'Declaración de Impuestos SENIAT',
            'mes' => $mes,
            'anio' => $anio,
        ]);
    }

    public function exportarIva(): void
    {
        $mes = $_GET['mes'] ?? date('m');
        $anio = $_GET['anio'] ?? date('Y');
        $rifAgente = "G200000000"; // RIF Institucional por defecto

        $retenciones = $this->repo->getByPeriodo($mes, $anio, 'IVA');

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="RET_IVA_'.$mes.'_'.$anio.'.txt"');

        foreach ($retenciones as $r) {
            // Formato SENIAT IVA TXT (Delimitado por TAB o similar, aquí usaremos ;)
            // RIF_AGENTE;PERIODO;FECHA_FACT;TIPO_OP;TIPO_DOC;RIF_PROV;NRO_FACT;NRO_CONT;MONTO_TOTAL;BASE;MONTO_RET;NRO_DOCTO_AFECT;NRO_COMP;MONTO_EXENTO;ALICUOTA;NRO_EXP
            $linea = [
                $rifAgente,
                $anio . $mes,
                $r['fecha_factura'],
                'C', // Compras
                '01', // Factura
                str_replace('-', '', $r['rif_proveedor']),
                $r['numero_factura'],
                $r['nro_control_d'],
                number_format($r['monto_total'], 2, '.', ''),
                number_format($r['monto_base'], 2, '.', ''),
                number_format($r['monto_retenido'], 2, '.', ''),
                '0',
                $r['numero_comprobante'],
                '0.00',
                number_format($r['porcentaje'], 2, '.', ''),
                '0',
            ];
            echo implode("\t", $linea) . "\r\n";
        }
        exit;
    }

    public function exportarIslr(): void
    {
        $mes = $_GET['mes'] ?? date('m');
        $anio = $_GET['anio'] ?? date('Y');
        $rifAgente = "G200000000";

        $retenciones = $this->repo->getByPeriodo($mes, $anio, 'ISLR');

        header('Content-Type: text/xml');
        header('Content-Disposition: attachment; filename="RET_ISLR_'.$mes.'_'.$anio.'.xml"');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<RelacionRetencionesISLR RifAgente="' . $rifAgente . '" Periodo="' . $anio . $mes . '">' . "\n";

        foreach ($retenciones as $r) {
            echo '  <DetalleRetencion>' . "\n";
            echo '    <RifRetenido>' . str_replace('-', '', $r['rif_proveedor']) . '</RifRetenido>' . "\n";
            echo '    <NumeroFactura>' . substr($r['numero_factura'], -10) . '</NumeroFactura>' . "\n";
            echo '    <NumeroControl>' . preg_replace('/[^0-9]/', '', $r['nro_control_d']) . '</NumeroControl>' . "\n";
            echo '    <FechaOperacion>' . date('d/m/Y', strtotime($r['fecha_emision'])) . '</FechaOperacion>' . "\n";
            echo '    <CodigoConcepto>' . ($r['codigo_seniat'] ?: '001') . '</CodigoConcepto>' . "\n";
            echo '    <MontoOperacion>' . number_format($r['monto_base'], 2, '.', '') . '</MontoOperacion>' . "\n";
            echo '    <PorcentajeRetencion>' . number_format($r['porcentaje'], 2, '.', '') . '</PorcentajeRetencion>' . "\n";
            echo '  </DetalleRetencion>' . "\n";
        }

        echo '</RelacionRetencionesISLR>';
        exit;
    }
}
