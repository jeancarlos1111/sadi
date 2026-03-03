<?php

namespace App\Controllers;

use App\Repositories\BancoRepository;
use App\Repositories\BeneficiarioRepository;
use App\Repositories\CuentaBancariaRepository;
use App\Repositories\MovimientoBancarioRepository;
use App\Repositories\TipoOperacionBancariaRepository;
use App\Services\ChequePdfService;
use Exception;

class ChequesController extends BaseController
{
    private MovimientoBancarioRepository $movRepo;
    private CuentaBancariaRepository $ctaRepo;
    private BancoRepository $bancoRepo;
    private TipoOperacionBancariaRepository $tipoRepo;
    private BeneficiarioRepository $benefRepo;

    public function __construct()
    {
        $this->movRepo = new MovimientoBancarioRepository();
        $this->ctaRepo = new CuentaBancariaRepository();
        $this->bancoRepo = new BancoRepository();
        $this->tipoRepo = new TipoOperacionBancariaRepository();
        $this->benefRepo = new BeneficiarioRepository();
    }

    public function emisionDirecta(): void
    {
        $cuentasRaw = $this->ctaRepo->all();
        $cuentas = [];
        foreach ($cuentasRaw as $c) {
            $banco = $this->bancoRepo->find($c->idBanco);
            $cuentas[] = [
                'id_cta_bancaria' => $c->id,
                'numero_cta_bancaria' => $c->numeroCuenta,
                'nombre_banco' => $banco ? $banco->nombreBanco : 'Desconocido',
            ];
        }

        $beneficiarios = $this->benefRepo->all();

        $this->renderView('banco/cheques/emision_directa', [
            'titulo' => 'Emisión de Cheque Directo',
            'cuentas' => $cuentas,
            'beneficiarios' => $beneficiarios,
            'fechaActual' => date('Y-m-d'),
        ]);
    }

    public function guardarDirecto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=cheques/emisionDirecta');

            return;
        }

        try {
            $idCta = (int)($_POST['id_cta_bancaria'] ?? 0);
            $beneficiarioNombre = $_POST['beneficiario_nombre'] ?? '';
            $monto = (float)($_POST['monto'] ?? 0);
            $referencia = trim($_POST['referencia'] ?? '');
            $concepto = trim($_POST['concepto'] ?? '');
            $fecha = $_POST['fecha'] ?? date('Y-m-d');

            if (!$idCta || $monto <= 0 || empty($referencia) || empty($beneficiarioNombre)) {
                throw new Exception("Datos incompletos para la emisión del cheque. Banco, Beneficiario, Monto y Nro. Cheque son requeridos.");
            }

            // Buscar ID de operacion tipo "CH" (Cheque)
            $tipos = $this->tipoRepo->all();
            $idTipoCh = null;
            foreach ($tipos as $t) {
                if ($t->acronimo === 'CH') {
                    $idTipoCh = $t->id;

                    break;
                }
            }

            if (!$idTipoCh) {
                throw new Exception("No existe el Tipo de Operación 'CH' (Cheque) en el sistema.");
            }

            // Para asociar el beneficiario y concepto, modificamos la referencia
            // Guardamos el beneficiario primero en la referencia para poder extraerlo en el voucher
            // Formato: NroCheque|Beneficiario|Concepto
            $refCompleta = $referencia . "|" . $beneficiarioNombre . "|" . $concepto;

            // Egreso bancario (monto negativo)
            $movimiento = new \App\Models\MovimientoBancario($idCta, $idTipoCh, -$monto, $fecha, $refCompleta);
            $this->movRepo->save($movimiento);

            header('Location: ?route=cheques/listado&success=' . urlencode('Cheque emitido exitosamente.'));
        } catch (Exception $e) {
            header('Location: ?route=cheques/emisionDirecta&error=' . urlencode($e->getMessage()));
        }
    }

    public function listado(): void
    {
        $idCta = (int)($_GET['id_cta_bancaria'] ?? 0);
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-t');

        $cuentasRaw = $this->ctaRepo->all();
        $cuentas = [];
        foreach ($cuentasRaw as $c) {
            $banco = $this->bancoRepo->find($c->idBanco);
            $cuentas[] = [
                'id_cta_bancaria' => $c->id,
                'numero_cta_bancaria' => $c->numeroCuenta,
                'nombre_banco' => $banco ? $banco->nombreBanco : 'Desconocido',
            ];
        }

        // Buscar ID de operacion tipo "CH"
        $tipos = $this->tipoRepo->all();
        $idTipoCh = null;
        foreach ($tipos as $t) {
            if ($t->acronimo === 'CH') {
                $idTipoCh = $t->id;

                break;
            }
        }

        $cheques = [];
        if ($idTipoCh) {
            $db = $this->movRepo->getPdo();
            $params = [$idTipoCh, $desde, $hasta];

            $sql = "SELECT m.*, c.numero_cta_bancaria, b.nombre_banco
                    FROM movimiento_bancario m
                    JOIN cta_bancaria c ON m.id_cta_bancaria = c.id_cta_bancaria
                    JOIN banco b ON c.id_banco = b.id_banco
                    WHERE m.id_tipo_operacion_bancaria = ? AND m.eliminado = 0
                    AND m.fecha BETWEEN ? AND ?";

            if ($idCta) {
                $sql .= " AND m.id_cta_bancaria = ?";
                $params[] = $idCta;
            }

            $sql .= " ORDER BY m.fecha DESC, m.id_movimiento_bancario DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $chequesObj = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($chequesObj as $ch) {
                // Parsear referencia si tiene el formato "Ref|Beneficiario|Concepto"
                $partes = explode('|', $ch['referencia']);
                $nroCheque = trim($partes[0] ?? $ch['referencia']);
                $beneficiario = count($partes) > 1 ? trim($partes[1]) : 'Desconocido/Pagador';
                $concepto = count($partes) > 2 ? trim($partes[2]) : 'No especificado';

                $cheques[] = [
                    'id' => $ch['id_movimiento_bancario'],
                    'fecha' => $ch['fecha'],
                    'banco' => $ch['nombre_banco'],
                    'cuenta' => $ch['numero_cta_bancaria'],
                    'nro_cheque' => $nroCheque,
                    'beneficiario' => $beneficiario,
                    'concepto' => $concepto,
                    'monto' => abs($ch['monto']), // Mostrar en positivo
                ];
            }
        }

        $asPdf = (bool)($_GET['pdf'] ?? false);

        if ($asPdf && !empty($cheques)) {
            $pdf = new \App\Services\PdfService();
            $pdf->AliasNbPages();
            $pdf->setTitulo("LISTADO DE CHEQUES EMITIDOS (" . date('d/m/Y', strtotime($desde)) . " AL " . date('d/m/Y', strtotime($hasta)) . ")");
            $pdf->AddPage();

            $cabecera = ['Fecha', 'Banco/Cheque', 'Beneficiario', 'Monto'];
            $filas = [];
            $total = 0;
            foreach ($cheques as $ch) {
                $filas[] = [
                    date('d/m/Y', strtotime($ch['fecha'])),
                    $ch['banco'] . "\nChq: " . $ch['nro_cheque'],
                    substr($ch['beneficiario'], 0, 40),
                    number_format($ch['monto'], 2, ',', '.'),
                ];
                $total += $ch['monto'];
            }
            $pdf->TablaElegante($cabecera, $filas);

            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 10, "MONTO TOTAL CHEQUES EMITIDOS: " . number_format($total, 2, ',', '.') . " Bs.", 0, 1, 'R');

            $pdf->Output('I', "Listado_Cheques.pdf");

            return;
        }

        $this->renderView('banco/cheques/listado', [
            'titulo' => 'Listado de Cheques Emitidos',
            'cuentas' => $cuentas,
            'idCuenta' => $idCta,
            'desde' => $desde,
            'hasta' => $hasta,
            'cheques' => $cheques,
        ]);
    }

    public function voucher(): void
    {
        $idMov = (int)($_GET['id'] ?? 0);
        if (!$idMov) {
            die("ID Inválido");
        }

        $db = $this->movRepo->getPdo();
        $stmt = $db->prepare("
            SELECT m.*, c.numero_cta_bancaria, b.nombre_banco
            FROM movimiento_bancario m
            JOIN cta_bancaria c ON m.id_cta_bancaria = c.id_cta_bancaria
            JOIN banco b ON c.id_banco = b.id_banco
            WHERE m.id_movimiento_bancario = ? AND m.eliminado = 0
        ");
        $stmt->execute([$idMov]);
        $mov = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$mov) {
            die("No se encontro el movimiento.");
        }

        // Parsear datos
        $partes = explode('|', $mov['referencia']);
        $nroCheque = trim($partes[0] ?? $mov['referencia']);
        $beneficiario = count($partes) > 1 ? trim($partes[1]) : 'A LA ORDEN DE';
        $concepto = count($partes) > 2 ? trim($partes[2]) : 'Cancelación de compromisos según soportes';

        $chequePdf = new ChequePdfService();
        $chequePdf->AliasNbPages();
        $chequePdf->AddPage();

        // Convertir monto a numero absoluto
        $montoNum = abs((float)$mov['monto']);

        $chequePdf->ImprimirChequeVoucher(
            $nroCheque,
            $montoNum,
            $beneficiario,
            $mov['fecha'],
            $concepto,
            $mov['nombre_banco'],
            $mov['numero_cta_bancaria']
        );

        $chequePdf->Output('I', "Voucher_Cheque_".$nroCheque.".pdf");
    }
}
