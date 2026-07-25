<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CuentaBancariaRepository;
use App\Repositories\MovimientoBancarioRepository;
use App\Repositories\SolicitudPagoRepository;
use App\Services\PdfService;
use Exception;
use PDOException;

class BancoController extends BaseController
{
    private MovimientoBancarioRepository $movRepo;
    private CuentaBancariaRepository $ctaRepo;
    private SolicitudPagoRepository $solRepo;
    private ?PdfService $pdfService;

    public function __construct(
        MovimientoBancarioRepository $movRepo,
        CuentaBancariaRepository $ctaRepo,
        SolicitudPagoRepository $solRepo,
        ?PdfService $pdfService = null
    ) {
        $this->movRepo = $movRepo;
        $this->ctaRepo = $ctaRepo;
        $this->solRepo = $solRepo;
        $this->pdfService = $pdfService;

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $movimientos = $this->movRepo->all($search);
            $cuentas = $this->ctaRepo->all();
        } catch (PDOException $e) {
            $movimientos = [];
            $cuentas = [];
            $error = "Error al obtener movimientos bancarios: " . $e->getMessage();
        }

        $this->renderView('banco/movimientos/index', [
            'titulo'      => 'Movimientos Bancarios',
            'movimientos' => $movimientos,
            'cuentas'     => $cuentas,
            'search'      => $search,
            'error'       => $error ?? null,
        ]);
    }

    /**
     * GET: Pantalla principal de Emisión de Pagos.
     * Lista las solicitudes de pago pendientes (no contabilizadas).
     */
    public function emitirPago(): void
    {
        try {
            // Solicitudes pendientes de pago (Usamos el repo de Solicitudes)
            $solicitudes = $this->solRepo->getPendientesPago();

            // Cuentas bancarias (Usamos el repo de Cuentas)
            $cuentasData = $this->ctaRepo->all();
            $cuentas = array_map(fn ($c) => [
                'id_cta_bancaria' => $c->id,
                'numero_cta_bancaria' => $c->numeroCuenta,
                'nombre_banco' => $c->nombreBanco,
            ], $cuentasData);

            // Tipos de operación bancaria
            $tiposOp = $this->movRepo->getTiposOperacion();

        } catch (PDOException $e) {
            $solicitudes = $cuentas = $tiposOp = [];
            $error = "Error al cargar datos: " . $e->getMessage();
        }

        $this->renderView('banco/pagos/emision', [
            'titulo'     => 'Emisión de Pagos y Cheques',
            'solicitudes' => $solicitudes,
            'cuentas'    => $cuentas,
            'tiposOp'    => $tiposOp,
            'error'      => $error ?? null,
            'success'    => $_GET['ok'] ?? null,
        ]);
    }

    /**
     * POST: Procesar el pago de una solicitud.
     */
    public function procesarPago(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=banco/emitirPago');
            exit;
        }

        $idSolicitud   = (int)($_POST['id_solicitud_pago'] ?? 0);
        $idCuenta      = (int)($_POST['id_cta_bancaria'] ?? 0);
        $idTipoOp      = (int)($_POST['id_tipo_operacion'] ?? 0);
        $referencia    = trim($_POST['referencia'] ?? '');
        $fecha         = trim($_POST['fecha'] ?? date('Y-m-d'));

        if (!$idSolicitud || !$idCuenta || !$idTipoOp) {
            header('Location: ?route=banco/emitirPago&error=campos_requeridos');
            exit;
        }

        try {
            // Usamos la lógica de pago de SolicitudPagoRepository para mantener integridad
            $dataPago = [
                'id_cta_bancaria' => $idCuenta,
                'id_tipo_operacion_bancaria' => $idTipoOp,
                'referencia' => $referencia,
                'fecha_pago' => $fecha,
            ];

            if ($this->solRepo->registrarPago($idSolicitud, $dataPago)) {
                header('Location: ?route=banco/emitirPago&ok=' . urlencode("Pago registrado y presupuesto afectado exitosamente. Ref: " . ($referencia ?: 'N/A')));
                exit;
            } else {
                throw new Exception("Error al procesar el pago.");
            }

        } catch (Exception $e) {
            header('Location: ?route=banco/emitirPago&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function estadoCuenta(): void
    {
        $idCuenta = (int)($_GET['id_cta_bancaria'] ?? 0);
        $desde    = $_GET['desde'] ?? date('Y-m-01');
        $hasta    = $_GET['hasta'] ?? date('Y-m-d');
        $isPdf    = (bool)($_GET['pdf'] ?? false);

        $cuentas = $this->ctaRepo->all();
        $movimientos = [];
        $saldoAnterior = 0.0;
        $cuentaSel = null;

        if ($idCuenta) {
            $cuentaSel = $this->ctaRepo->find($idCuenta);
            $movimientos = $this->movRepo->getByAccount($idCuenta, $desde, $hasta);
            $saldoAnterior = $this->movRepo->getSaldoAnterior($idCuenta, $desde);
        }

        if ($isPdf && $idCuenta && $this->pdfService) {
            $this->pdfEstadoCuenta($cuentaSel, $movimientos, $saldoAnterior, $desde, $hasta);

            return;
        }

        $this->renderView('banco/reportes/estado_cuenta', [
            'titulo'        => 'Estado de Cuenta Bancaria',
            'cuentas'       => $cuentas,
            'movimientos'   => $movimientos,
            'saldoAnterior' => $saldoAnterior,
            'idCuenta'      => $idCuenta,
            'desde'         => $desde,
            'hasta'         => $hasta,
            'cuentaSel'     => $cuentaSel,
        ]);
    }

    private function pdfEstadoCuenta($cta, $movs, $saldoAnt, $desde, $hasta): void
    {
        $this->pdfService->setTitulo("ESTADO DE CUENTA BANCARIA (" . date('d/m/Y', strtotime($desde)) . " AL " . date('d/m/Y', strtotime($hasta)) . ")");
        $this->pdfService->AliasNbPages();
        $this->pdfService->AddPage();

        $pdf = $this->pdfService;
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(40, 7, "BANCO:", 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, $cta->nombreBanco, 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(40, 7, "CUENTA:", 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, $cta->numeroCuenta, 0, 1);
        $pdf->Ln(5);

        // Header Tabla
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(25, 7, "FECHA", 1, 0, 'C', true);
        $pdf->Cell(35, 7, "REFERENCIA", 1, 0, 'C', true);
        $pdf->Cell(60, 7, "CONCEPTO / OPERACION", 1, 0, 'C', true);
        $pdf->Cell(35, 7, "MONTO (Bs)", 1, 0, 'C', true);
        $pdf->Cell(35, 7, "SALDO (Bs)", 1, 1, 'C', true);

        // Saldo Inicial
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(120, 7, "SALDO ANTERIOR AL " . date('d/m/Y', strtotime($desde)), 1, 0, 'R');
        $pdf->Cell(35, 7, "", 1, 0);
        $pdf->Cell(35, 7, number_format($saldoAnt, 2, ',', '.'), 1, 1, 'R');

        $pdf->SetFont('Arial', '', 9);
        $currentSaldo = $saldoAnt;
        foreach ($movs as $m) {
            $currentSaldo += (float)$m['monto'];
            $pdf->Cell(25, 7, date('d/m/Y', strtotime($m['fecha'])), 1, 0, 'C');
            $pdf->Cell(35, 7, $m['referencia'], 1, 0, 'C');
            $pdf->Cell(60, 7, substr($m['nombre_tipo_operacion_bancaria'], 0, 30), 1, 0, 'L');
            $pdf->Cell(35, 7, number_format($m['monto'], 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(35, 7, number_format($currentSaldo, 2, ',', '.'), 1, 1, 'R');
        }

        // Totales Finales
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(120, 10, "SALDO DISPONIBLE ACTUALIZADO AL " . date('d/m/Y', strtotime($hasta)), 1, 0, 'R', true);
        $pdf->Cell(70, 10, number_format($currentSaldo, 2, ',', '.') . " Bs.", 1, 1, 'R', true);

        $pdf->Output('I', 'EstadoCuenta_' . $cta->id . '.pdf');
    }

    public function reversar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=banco/index');
            exit;
        }

        $idSolicitud = (int)($_POST['id_solicitud'] ?? 0);
        if (!$idSolicitud) {
            header('Location: ?route=banco/index&error=ID de solicitud no válido');
            exit;
        }

        try {
            if ($this->solRepo->reversarPago($idSolicitud)) {
                header('Location: ?route=banco/index&success=Pago reversado correctamente. Los fondos han sido restaurados.');
            } else {
                throw new Exception("Error al procesar la reversión");
            }
        } catch (Exception $e) {
            header('Location: ?route=banco/index&error=' . urlencode($e->getMessage()));
        }
    }

    public function conciliacion(): void
    {
        $idCuenta = (int)($_GET['id_cta_bancaria'] ?? 0);
        $mes = (int)($_GET['mes'] ?? date('m'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        
        $cuentas = $this->ctaRepo->all();
        $movimientos = [];
        $saldoLibros = 0.0;
        $cuentaSel = null;
        $fechaConciliacion = sprintf('%04d-%02d-%02d', $anio, $mes, cal_days_in_month(CAL_GREGORIAN, $mes, $anio));

        if ($idCuenta) {
            $cuentaSel = $this->ctaRepo->find($idCuenta);
            $movimientos = $this->movRepo->getMovimientosParaConciliar($idCuenta, $fechaConciliacion, $fechaConciliacion);
            $saldoLibros = $this->movRepo->getSaldoLibros($idCuenta, $fechaConciliacion);
        }

        $this->renderView('banco/reportes/conciliacion', [
            'titulo'      => 'Conciliación Bancaria',
            'cuentas'     => $cuentas,
            'movimientos' => $movimientos,
            'saldoLibros' => $saldoLibros,
            'idCuenta'    => $idCuenta,
            'mes'         => $mes,
            'anio'        => $anio,
            'cuentaSel'   => $cuentaSel,
            'fechaConciliacion' => $fechaConciliacion,
            'error'       => $_GET['error'] ?? null,
            'success'     => $_GET['success'] ?? null,
        ]);
    }

    public function procesarConciliacion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=banco/conciliacion');
            exit;
        }

        $idCuenta = (int)($_POST['id_cta_bancaria'] ?? 0);
        $fechaConciliacion = $_POST['fecha_conciliacion'] ?? '';
        $saldoBancoStr = $_POST['saldo_estado_cuenta'] ?? '0';
        $idsSeleccionados = $_POST['movimientos'] ?? []; 
        $idsSeleccionados = array_map('intval', $idsSeleccionados);

        if (!$idCuenta || !$fechaConciliacion) {
            header('Location: ?route=banco/conciliacion&error=' . urlencode("Faltan parámetros requeridos"));
            exit;
        }

        try {
            $saldoBancoStr = str_replace('.', '', $saldoBancoStr);
            $saldoBancoStr = str_replace(',', '.', $saldoBancoStr);
            $saldoBanco = (float)$saldoBancoStr;

            $saldoLibros = $this->movRepo->getSaldoLibros($idCuenta, $fechaConciliacion);
            
            $movimientosEnPantalla = $this->movRepo->getMovimientosParaConciliar($idCuenta, $fechaConciliacion, $fechaConciliacion);
            
            $sumaNoConciliados = 0.0;
            foreach ($movimientosEnPantalla as $mov) {
                if (!in_array((int)$mov['id_movimiento_bancario'], $idsSeleccionados)) {
                    $sumaNoConciliados += (float)$mov['monto'];
                }
            }

            $saldoCalculado = $saldoBanco + $sumaNoConciliados;

            if (abs($saldoCalculado - $saldoLibros) > 0.01) {
                throw new Exception("El saldo no cuadra. Libros: " . number_format($saldoLibros, 2, ',', '.') . " | Calculado: " . number_format($saldoCalculado, 2, ',', '.'));
            }

            $this->movRepo->procesarConciliacionMasiva($idCuenta, $fechaConciliacion, $idsSeleccionados);

            $qs = http_build_query(['route' => 'banco/conciliacion', 'id_cta_bancaria' => $idCuenta, 'mes' => date('m', strtotime($fechaConciliacion)), 'anio' => date('Y', strtotime($fechaConciliacion)), 'success' => 'Conciliación guardada exitosamente y cuadrada.']);
            header('Location: ?' . $qs);
            exit;
        } catch (Exception $e) {
            $qs = http_build_query(['route' => 'banco/conciliacion', 'id_cta_bancaria' => $idCuenta, 'mes' => date('m', strtotime($fechaConciliacion)), 'anio' => date('Y', strtotime($fechaConciliacion)), 'error' => $e->getMessage()]);
            header('Location: ?' . $qs);
            exit;
        }
    }
}
