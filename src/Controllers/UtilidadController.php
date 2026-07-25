<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UtilidadRepository;
use App\Repositories\FichaRepository;

class UtilidadController extends BaseController
{
    private UtilidadRepository $utilidadRepo;

    public function __construct()
    {
        $this->utilidadRepo = new UtilidadRepository();

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        \App\Auth\Gate::authorize('nomina.planillas.ver');

        $utilidades = $this->utilidadRepo->all();
        
        $this->renderView('utilidades/index', [
            'titulo' => 'Histórico de Utilidades Anuales',
            'utilidades' => $utilidades
        ]);
    }

    public function crear(): void
    {
        \App\Auth\Gate::authorize('nomina.planillas.ver');

        $this->renderView('utilidades/crear', [
            'titulo' => 'Generar Utilidades / Fin de Año',
            'anioActual' => (int)date('Y')
        ]);
    }

    public function simular(): void
    {
        \App\Auth\Gate::authorize('nomina.planillas.ver');

        if (!isset($_POST['anio']) || !isset($_POST['dias_base'])) {
            echo "<div class='alert alert-danger'>Faltan parámetros.</div>";
            return;
        }

        $anio = (int)$_POST['anio'];
        $diasBase = (int)$_POST['dias_base'];

        if ($this->utilidadRepo->existeUtilidad($anio)) {
            echo "<div class='alert alert-warning mb-0'><i class='fas fa-exclamation-triangle'></i> <strong>Advertencia:</strong> Ya se generaron y pagaron utilidades para el año {$anio}.</div>";
            return;
        }

        $resultado = $this->utilidadRepo->generarSimulacionMasiva($anio, $diasBase);
        $utilidad = $resultado['utilidad'];
        $detalles = $resultado['detalles'];

        if (empty($detalles)) {
            echo "<div class='alert alert-info'>No hay trabajadores activos elegibles para el cálculo.</div>";
            return;
        }

        // Render Server-Driven UI (HTML-over-AJAX)
        $html = "
            <div class='alert alert-info'>
                <i class='fas fa-info-circle'></i> <strong>Total Nómina Proyectada:</strong> Bs " . number_format($utilidad->montoTotalNomina, 2, ',', '.') . " 
                (Cálculo sobre {$diasBase} días)
            </div>
            
            <div class='table-responsive'>
                <table class='table table-bordered table-striped table-sm text-sm'>
                    <thead class='thead-dark'>
                        <tr>
                            <th>Cód. Ficha</th>
                            <th>Fecha Ingreso</th>
                            <th class='text-center'>Meses a Pagar</th>
                            <th class='text-right'>Salario Mensual (Bs)</th>
                            <th class='text-right'>Monto Total (Bs)</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        foreach ($detalles as $det) {
            $html .= "
                <tr>
                    <td>{$det->codFicha}</td>
                    <td>{$det->fechaIngresoCalculo}</td>
                    <td class='text-center'>{$det->mesesLaborados} / 12</td>
                    <td class='text-right'>" . number_format($det->salarioBase, 2, ',', '.') . "</td>
                    <td class='text-right font-weight-bold text-success'>" . number_format($det->montoPagar, 2, ',', '.') . "</td>
                </tr>
            ";
        }

        $html .= "
                    </tbody>
                </table>
            </div>
            <input type='hidden' name='confirmar_calculo' value='1'>
        ";

        echo $html;
    }

    public function guardar(): void
    {
        \App\Auth\Gate::authorize('nomina.planillas.ver');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $anio = (int)$_POST['anio'];
            $diasBase = (int)$_POST['dias_base'];

            if ($this->utilidadRepo->existeUtilidad($anio)) {
                header('Location: ?route=utilidad/index&error=' . urlencode('Ya existe una nómina de utilidades para ese año.'));
                exit;
            }

            $resultado = $this->utilidadRepo->generarSimulacionMasiva($anio, $diasBase);
            $utilidad = $resultado['utilidad'];
            $detalles = $resultado['detalles'];

            $idGenerado = $this->utilidadRepo->save($utilidad, $detalles);

            if ($idGenerado) {
                $this->audit('utilidades', 'CREAR', $idGenerado, null, [
                    'anio' => $anio,
                    'dias_base' => $diasBase,
                    'total_trabajadores' => count($detalles),
                    'monto_total' => $utilidad->montoTotalNomina
                ]);
                header('Location: ?route=utilidad/index&msg=' . urlencode('Nómina de utilidades generada correctamente.'));
            } else {
                header('Location: ?route=utilidad/index&error=' . urlencode('Error al generar la nómina de utilidades.'));
            }
            exit;
        }
    }

    public function imprimirListado(): void
    {
        \App\Auth\Gate::authorize('nomina.planillas.ver');
        
        $idUtilidad = (int)($_GET['id'] ?? 0);
        $utilidad = $this->utilidadRepo->find($idUtilidad);
        
        if (!$utilidad) {
            die('Utilidad no encontrada.');
        }
        
        $detalles = $this->utilidadRepo->getDetalles($idUtilidad);
        
        require_once __DIR__ . '/../Libs/fpdf.php';
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        
        // Cabecera
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, mb_convert_encoding('Nómina de Utilidades / Bonificación Fin de Año ' . $utilidad['anio'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, mb_convert_encoding('Días Base: ' . $utilidad['dias_base'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Cell(0, 7, mb_convert_encoding('Total a Pagar: Bs ' . number_format((float)$utilidad['monto_total_nomina'], 2, ',', '.'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Tabla
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(25, 7, mb_convert_encoding('Cédula', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(65, 7, mb_convert_encoding('Trabajador', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
        $pdf->Cell(25, 7, mb_convert_encoding('Ingreso', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(15, 7, mb_convert_encoding('Meses', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(30, 7, mb_convert_encoding('Salario', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(30, 7, mb_convert_encoding('Total Pago', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        $pdf->SetFont('Arial', '', 9);
        foreach ($detalles as $det) {
            $pdf->Cell(25, 7, $det['cedula'], 1, 0, 'C');
            $pdf->Cell(65, 7, mb_convert_encoding($det['nombres'] . ' ' . $det['apellidos'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
            $pdf->Cell(25, 7, $det['fecha_ingreso_calculo'], 1, 0, 'C');
            $pdf->Cell(15, 7, $det['meses_laborados'], 1, 0, 'C');
            $pdf->Cell(30, 7, number_format((float)$det['salario_base'], 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(30, 7, number_format((float)$det['monto_pagar'], 2, ',', '.'), 1, 1, 'R');
        }
        
        $pdf->Output('I', 'listado_utilidades_' . $utilidad['anio'] . '.pdf');
    }
    
    public function imprimirRecibo(): void
    {
        \App\Auth\Gate::authorize('nomina.planillas.ver');
        
        $idUtilidad = (int)($_GET['id_utilidad'] ?? 0);
        $codFicha = (int)($_GET['cod_ficha'] ?? 0);
        
        $utilidad = $this->utilidadRepo->find($idUtilidad);
        if (!$utilidad) {
            die('Utilidad no encontrada.');
        }
        
        $detalles = $this->utilidadRepo->getDetalles($idUtilidad);
        $detalleTrabajador = null;
        foreach ($detalles as $det) {
            if ((int)$det['cod_ficha'] === $codFicha) {
                $detalleTrabajador = $det;
                break;
            }
        }
        
        if (!$detalleTrabajador) {
            die('Detalle de trabajador no encontrado para esta nómina de utilidades.');
        }
        
        require_once __DIR__ . '/../Libs/fpdf.php';
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, mb_convert_encoding('Recibo de Pago de Utilidades ' . $utilidad['anio'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Ln(10);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 8, mb_convert_encoding('Trabajador:', 'ISO-8859-1', 'UTF-8'));
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, mb_convert_encoding($detalleTrabajador['nombres'] . ' ' . $detalleTrabajador['apellidos'], 'ISO-8859-1', 'UTF-8'), 0, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 8, mb_convert_encoding('Cédula:', 'ISO-8859-1', 'UTF-8'));
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, $detalleTrabajador['cedula'], 0, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 8, mb_convert_encoding('Ingreso:', 'ISO-8859-1', 'UTF-8'));
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, $detalleTrabajador['fecha_ingreso_calculo'], 0, 1);
        
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, mb_convert_encoding('Detalle del Cálculo (LOTTT Art. 131)', 'ISO-8859-1', 'UTF-8'), 'B', 1);
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(80, 8, mb_convert_encoding('Concepto', 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(110, 8, mb_convert_encoding('Valor', 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $pdf->SetFont('Arial', '', 10);
        
        $pdf->Cell(80, 8, mb_convert_encoding('Meses Proporcionales (Max 12):', 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(110, 8, $detalleTrabajador['meses_laborados'], 0, 1, 'R');
        
        $pdf->Cell(80, 8, mb_convert_encoding('Días Base Aprobados:', 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(110, 8, $utilidad['dias_base'], 0, 1, 'R');
        
        $pdf->Cell(80, 8, mb_convert_encoding('Salario Normal Mensual:', 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(110, 8, 'Bs ' . number_format((float)$detalleTrabajador['salario_base'], 2, ',', '.'), 0, 1, 'R');
        
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(80, 10, mb_convert_encoding('TOTAL A COBRAR:', 'ISO-8859-1', 'UTF-8'), 'T');
        $pdf->Cell(110, 10, 'Bs ' . number_format((float)$detalleTrabajador['monto_pagar'], 2, ',', '.'), 'T', 1, 'R');
        
        $pdf->Ln(30);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(95, 8, '___________________________________', 0, 0, 'C');
        $pdf->Cell(95, 8, '___________________________________', 0, 1, 'C');
        $pdf->Cell(95, 6, mb_convert_encoding('Firma del Trabajador', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
        $pdf->Cell(95, 6, mb_convert_encoding('Firma Autorizada', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        
        $pdf->Output('I', 'recibo_utilidades_' . $detalleTrabajador['cedula'] . '_' . $utilidad['anio'] . '.pdf');
    }
}
