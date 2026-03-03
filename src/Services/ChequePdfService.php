<?php

namespace App\Services;

require_once __DIR__ . '/../Libs/fpdf.php';

use FPDF;

/**
 * Servicio especial para imprimir Cheque Voucher, el cual tiene márgenes
 * muy precisos para encajar en el cheque pre-impreso y el formato de voucher abajo.
 */
class ChequePdfService extends FPDF
{
    // No imprime cabecera general ni footer global, ya que el voucher es un formato autogestionado en la página
    public function Header(): void
    {
        // Sin header global
    }

    public function Footer(): void
    {
        // Sin footer global
    }

    /**
     * Convierte un número a letras (Aproximación simple para bolívares)
     */
    private function numeroALetras(float $numero): string
    {
        // Esta es un implementación simplificada. En un entorno real se usa una biblioteca como NumerosEnLetras
        $miles = floor($numero / 1000);
        $resto = fmod($numero, 1000);

        // Por simplicidad en la simulación devolvemos este formato
        return "** EXACTAMENTE LA CANTIDAD DESCRITA EN LOS DÍGITOS **";
    }

    public function ImprimirChequeVoucher($nroCheque, $monto, $beneficiario, $fecha, $concepto, $banco, $cuenta): void
    {
        // --- PARTE 1: ESPACIO PARA EL CHEQUE PREIMPRESO (Aprox. los primeros 8 cm de la página) ---
        $this->SetY(15);
        $this->SetFont('Arial', 'B', 12);

        // Monto en número (esquina superior derecha)
        $this->SetX(-60);
        $montoStr = "*** " . number_format($monto, 2, ',', '.') . " ***";
        $this->Cell(40, 10, $montoStr, 0, 1, 'R');

        // Beneficiario y monto en letras
        $this->SetY(40);
        $this->SetX(20);
        $this->Cell(20, 10, "Paguese a la orden de: ");
        $this->SetX(65);
        $this->Cell(120, 10, mb_convert_encoding($beneficiario, 'ISO-8859-1', 'UTF-8'), "B", 1, 'L');

        $this->SetX(20);
        $this->Cell(20, 10, "La cantidad de: ");
        $this->SetX(55);
        $montoLetras = "*** " . number_format($monto, 2, ',', '.') . " BOLIVARES ***";
        $this->Cell(130, 10, mb_convert_encoding($montoLetras, 'ISO-8859-1', 'UTF-8'), "B", 1, 'L');

        // Fecha y Lugar
        $this->SetX(20);
        $this->Cell(20, 10, "Lugar y Fecha: ");
        $this->SetX(55);
        $fechaStr = "Caracas, " . date('d/m/Y', strtotime($fecha));
        $this->Cell(130, 10, mb_convert_encoding($fechaStr, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

        // Línea de corte (para indicar fin de la parte del cheque e inicio del voucher)
        $this->SetY(95);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 5, "------------------------------------------------------------- Doblar o Cortar Aquí -------------------------------------------------------------", 0, 1, 'C');

        // --- PARTE 2: VOUCHER CONTABLE (Centro / Abajo) ---
        $this->SetY(105);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, "COMPROBANTE DE EGRESO - VOUCHER", 1, 1, 'C');

        $this->Ln(5);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(30, 8, "Banco:", 1);
        $this->SetFont('Arial', '', 10);
        $this->Cell(70, 8, mb_convert_encoding($banco, 'ISO-8859-1', 'UTF-8'), 1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(30, 8, "Cheque Nro:", 1);
        $this->SetFont('Arial', '', 10);
        $this->Cell(60, 8, mb_convert_encoding($nroCheque, 'ISO-8859-1', 'UTF-8'), 1, 1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(30, 8, "Cuenta:", 1);
        $this->SetFont('Arial', '', 10);
        $this->Cell(70, 8, mb_convert_encoding($cuenta, 'ISO-8859-1', 'UTF-8'), 1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(30, 8, "Fecha:", 1);
        $this->SetFont('Arial', '', 10);
        $this->Cell(60, 8, date('d/m/Y', strtotime($fecha)), 1, 1);

        $this->Ln(5);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(30, 8, "Beneficiario:", 1);
        $this->SetFont('Arial', '', 10);
        $this->Cell(160, 8, mb_convert_encoding($beneficiario, 'ISO-8859-1', 'UTF-8'), 1, 1);

        // Concepto Multilínea
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 8, "Por Concepto De:", 'LTR', 1, 'L');
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 6, mb_convert_encoding($concepto, 'ISO-8859-1', 'UTF-8'), 'LRB', 'L');

        $this->Ln(5);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 8, "Monto Total: " . number_format($monto, 2, ',', '.') . " Bs.", 1, 1, 'R');

        // Firmas
        $this->Ln(15);
        $ancho = $this->GetPageWidth() - 20;
        $this->Cell($ancho / 3, 5, 'PREPARADO POR', 'T', 0, 'C');
        $this->Cell($ancho / 3, 5, 'REVISADO POR', 'T', 0, 'C');
        $this->Cell($ancho / 3, 5, 'RECIBE CONFORME (BENEF.)', 'T', 1, 'C');
    }
}
