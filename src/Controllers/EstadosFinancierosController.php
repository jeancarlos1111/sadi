<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

use App\Repositories\EstadoFinancieroRepository;
use App\Services\PdfService;

class EstadosFinancierosController extends BaseController
{
    private EstadoFinancieroRepository $repo;

    public function __construct()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
        $this->repo = new EstadoFinancieroRepository();
    }

    public function balanceComprobacion(): void
    {
        $fechaDesde = $_GET['fecha_desde'] ?? date('Y-01-01');
        $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

        $resultados = $this->repo->getBalanceComprobacion($fechaDesde, $fechaHasta);

        if (isset($_GET['pdf'])) {
            $this->generarPdfBalanceComprobacion($fechaDesde, $fechaHasta, $resultados);
            return;
        }

        $this->renderView('contabilidad/reportes/balance_comprobacion', [
            'titulo' => 'Balance de Comprobación',
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'resultados' => $resultados,
        ]);
    }

    private function generarPdfBalanceComprobacion(string $fechaDesde, string $fechaHasta, array $resultados): void
    {
        $pdf = new PdfService();
        $pdf->AliasNbPages();
        $pdf->setTitulo('Balance de Comprobación');
        $pdf->AddPage();
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, mb_convert_encoding('Período: ' . $fechaDesde . ' al ' . $fechaHasta, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Ln(5);

        $cabecera = ['Código', 'Denominación', 'Suma Debe (Bs)', 'Suma Haber (Bs)'];
        $datos = [];
        $totalDebe = 0;
        $totalHaber = 0;

        foreach ($resultados as $row) {
            $debe = (float)$row['total_debe'];
            $haber = (float)$row['total_haber'];
            if ($debe == 0 && $haber == 0) continue;

            $totalDebe += $debe;
            $totalHaber += $haber;

            $datos[] = [
                $row['codigo'],
                mb_convert_encoding($row['denominacion'], 'ISO-8859-1', 'UTF-8'),
                number_format($debe, 2, ',', '.'),
                number_format($haber, 2, ',', '.')
            ];
        }

        $pdf->TablaElegante($cabecera, $datos);

        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 10);
        
        $ancho = $pdf->GetPageWidth() - 20;
        $pdf->Cell($ancho / 2, 8, 'SUMAS IGUALES:', 0, 0, 'R');
        $pdf->Cell($ancho / 4, 8, 'Bs ' . number_format($totalDebe, 2, ',', '.'), 1, 0, 'R');
        $pdf->Cell($ancho / 4, 8, 'Bs ' . number_format($totalHaber, 2, ',', '.'), 1, 1, 'R');

        $pdf->Output('I', 'Balance_Comprobacion.pdf');
    }

    public function estadoResultados(): void
    {
        $fechaDesde = $_GET['fecha_desde'] ?? date('Y-01-01');
        $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

        $resultados = $this->repo->getEstadoResultados($fechaDesde, $fechaHasta);

        $this->renderView('contabilidad/reportes/estado_resultados', [
            'titulo' => 'Estado de Resultados',
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'resultados' => $resultados,
        ]);
    }

    public function balanceGeneral(): void
    {
        $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

        $resultados = $this->repo->getBalanceGeneral($fechaHasta);

        $this->renderView('contabilidad/reportes/balance_general', [
            'titulo' => 'Balance General (Estado de Situación Financiera)',
            'fechaHasta' => $fechaHasta,
            'resultados' => $resultados,
        ]);
    }
}
