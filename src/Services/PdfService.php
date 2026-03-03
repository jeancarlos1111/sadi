<?php

namespace App\Services;

// Requerir directamente FPDF sin dependencias (No namespaces)
require_once __DIR__ . '/../Libs/fpdf.php';

use FPDF;

class PdfService extends FPDF
{
    private string $tituloReporte = '';

    public function setTitulo(string $titulo): void
    {
        $this->tituloReporte = $titulo;
    }

    // Cabecera de página
    public function Header(): void
    {
        // Logo estático SADI simulado o texto
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80, 10, mb_convert_encoding('REPÚBLICA BOLIVARIANA DE VENEZUELA', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->Ln(6);
        $this->SetFont('Arial', '', 10);
        $this->Cell(80, 10, 'SISTEMA ADMINISTRATIVO INTEGRADO (SADI)', 0, 0, 'L');

        $this->Ln(15);

        // Título
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, mb_convert_encoding($this->tituloReporte, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $this->Ln(15);
    }

    // Pie de página
    public function Footer(): void
    {
        // Posición: a 1,5 cm del final
        $this->SetY(-30);
        $this->SetFont('Arial', 'B', 8);

        // Cajas de firmas obligatorias en la administración pública
        $ancho = $this->GetPageWidth() - 20;
        $this->Cell($ancho / 3, 5, 'PREPARADO POR', 'T', 0, 'C');
        $this->Cell($ancho / 3, 5, 'REVISADO POR', 'T', 0, 'C');
        $this->Cell($ancho / 3, 5, 'AUTORIZADO POR', 'T', 1, 'C');

        $this->SetY(-15);
        // Número de página
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8').$this->PageNo().'/{nb} - Impreso por SADI', 0, 0, 'R');
    }

    // Utilidad de tabla sencilla
    public function TablaElegante(array $cabecera, array $datos): void
    {
        // Colores, ancho de línea y fuente negrita
        $this->SetFillColor(33, 37, 41);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');

        // Cabecera (Sorteando anchos por cantidad de cols, máximo 190)
        $numCols = count($cabecera);
        $w = array_fill(0, $numCols, 190 / $numCols);

        for ($i = 0; $i < $numCols; $i++) {
            $this->Cell($w[$i], 7, mb_convert_encoding($cabecera[$i], 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        }
        $this->Ln();

        // Restauración de colores y fuentes
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');

        // Datos
        $fill = false;
        foreach ($datos as $row) {
            $i = 0;
            foreach ($row as $col) {
                // Alineación al centro a menos que sea el monto (últimos)
                $align = is_numeric(str_replace(['.', ','], '', $col)) ? 'R' : 'C';
                $this->Cell($w[$i], 6, mb_convert_encoding($col, 'ISO-8859-1', 'UTF-8'), 'LR', 0, $align, $fill);
                $i++;
            }
            $this->Ln();
            $fill = !$fill;
        }
        // Línea de cierre
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln();
    }
}
