<?php

declare(strict_types=1);

namespace App\Services;

// Requerir directamente FPDF sin dependencias (No namespaces)
require_once __DIR__ . '/../Libs/fpdf.php';

use FPDF;
use App\Repositories\InstitucionRepository;
use App\Models\Institucion;

class PdfService extends FPDF
{
    private string $tituloReporte = '';
    private Institucion $institucion;
    private array $firmas = ['PREPARADO POR', 'REVISADO POR', 'AUTORIZADO POR'];

    public function __construct($orientation='P', $unit='mm', $size='A4')
    {
        parent::__construct($orientation, $unit, $size);
        $repo = new InstitucionRepository();
        $this->institucion = $repo->getConfig();
    }

    public function setTitulo(string $titulo): void
    {
        $this->tituloReporte = $titulo;
    }

    public function setFirmas(array $firmas): void
    {
        $this->firmas = $firmas;
    }

    // Cabecera de página
    public function Header(): void
    {
        // 1. Marca de agua
        if ($this->institucion->marca_agua_activa) {
            $this->SetFont('Arial', 'B', 30);
            $this->SetTextColor(235, 235, 235); // Gris muy claro
            $x = $this->GetX();
            $y = $this->GetY();
            $this->SetXY(20, 130);
            $this->Cell(0, 0, 'SADI - DOCUMENTO OFICIAL', 0, 0, 'C');
            $this->SetXY($x, $y);
            $this->SetTextColor(0); // Restore color
        }

        // 2. Logo institucional
        if ($this->institucion->logo_path && file_exists(__DIR__ . '/../../public' . $this->institucion->logo_path)) {
            $this->Image(__DIR__ . '/../../public' . $this->institucion->logo_path, 10, 8, 33);
            $this->SetX(45);
        } else {
            $this->SetX(10);
        }

        // 3. Encabezado de la Institución
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, mb_convert_encoding('REPÚBLICA BOLIVARIANA DE VENEZUELA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 10);
        $nombreInstitucion = $this->institucion->nombre !== 'Configurar Institución' ? $this->institucion->nombre : 'SISTEMA ADMINISTRATIVO INTEGRADO (SADI)';
        $this->Cell(0, 5, mb_convert_encoding(strtoupper($nombreInstitucion), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        
        if ($this->institucion->rif) {
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 5, 'RIF: ' . $this->institucion->rif, 0, 1, 'C');
        }

        $this->Ln(10);

        // 4. Título del Reporte
        $this->SetFont('Arial', 'B', 14);
        $this->SetFillColor(15, 76, 117); // Azul institucional #0F4C75
        $this->SetTextColor(255);
        $this->Cell(0, 10, mb_convert_encoding($this->tituloReporte, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $this->SetTextColor(0);
        $this->Ln(15);
    }

    // Pie de página
    public function Footer(): void
    {
        // Posición: a 3 cm del final
        $this->SetY(-30);
        $this->SetFont('Arial', 'B', 8);

        // Calcular ancho por firma
        $numFirmas = count($this->firmas);
        if ($numFirmas > 0) {
            $anchoFirma = ($this->GetPageWidth() - 20) / $numFirmas;
            foreach ($this->firmas as $firma) {
                $this->Cell($anchoFirma, 5, mb_convert_encoding($firma, 'ISO-8859-1', 'UTF-8'), 'T', 0, 'C');
            }
            $this->Ln();
        }

        $this->SetY(-15);
        // Número de página
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8').$this->PageNo().'/{nb} - Impreso por SADI', 0, 0, 'C');
    }

    // Utilidad de tabla sencilla
    public function TablaElegante(array $cabecera, array $datos, array $anchos = []): void
    {
        // Colores, ancho de línea y fuente negrita
        $this->SetFillColor(15, 76, 117); // Azul institucional #0F4C75
        $this->SetTextColor(255);
        $this->SetDrawColor(200, 200, 200); // Borde gris suave
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 10);

        $numCols = count($cabecera);
        if (empty($anchos)) {
            $w = array_fill(0, $numCols, ($this->GetPageWidth() - 20) / $numCols);
        } else {
            $w = $anchos;
        }

        for ($i = 0; $i < $numCols; $i++) {
            $this->Cell($w[$i], 8, mb_convert_encoding($cabecera[$i], 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        }
        $this->Ln();

        // Restauración de colores y fuentes para los datos
        $this->SetFillColor(245, 245, 245);
        $this->SetTextColor(50, 50, 50);
        $this->SetFont('Arial', '', 9);

        // Datos
        $fill = false;
        foreach ($datos as $row) {
            $i = 0;
            foreach ($row as $col) {
                $colStr = (string)$col;
                // Alineación: derecha para montos/cantidades
                $align = is_numeric(str_replace(['.', ','], '', $colStr)) ? 'R' : 'L';
                if ($i === 0 && count($row) > 1 && is_numeric($colStr)) {
                     $align = 'C'; 
                }
                
                // Si la columna es texto y es la descripción (suele ser la más ancha), forzamos L
                if (isset($w[$i]) && $w[$i] > 60 && !is_numeric(str_replace(['.', ','], '', $colStr))) {
                    $align = 'L';
                }

                $ancho_col = $w[$i] ?? 20; // fallback
                $this->Cell($ancho_col, 7, mb_convert_encoding(substr($colStr, 0, 100), 'ISO-8859-1', 'UTF-8'), 'LR', 0, $align, $fill);
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
