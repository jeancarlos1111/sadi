<?php

namespace App\Services;

require_once __DIR__ . '/../Libs/fpdf.php';

use FPDF;

/**
 * Servicio PDF para el Mayor Analítico Presupuestario.
 * Genera un reporte detallado por estructura/cuenta mostrando
 * todos los movimientos y el saldo disponible acumulado.
 */
class MayorAnaliticoPdfService extends FPDF
{
    private string $anio;
    private string $entidad = 'REPÚBLICA BOLIVARIANA DE VENEZUELA';
    private string $organismo = 'SISTEMA ADMINISTRATIVO INTEGRADO (SADI)';

    public function __construct(string $anio = '')
    {
        parent::__construct('L', 'mm', 'A4'); // Landscape para más columnas
        $this->anio = $anio ?: date('Y');
        $this->AliasNbPages();
    }

    public function Header(): void
    {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, mb_convert_encoding($this->entidad, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, mb_convert_encoding($this->organismo, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 7, mb_convert_encoding("MAYOR ANALÍTICO PRESUPUESTARIO - AÑO {$this->anio}", 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, mb_convert_encoding('Fecha de emisión: ' . date('d/m/Y H:i'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');
        $this->Ln(3);
    }

    public function Footer(): void
    {
        $this->SetY(-20);
        $ancho = $this->GetPageWidth() - 20;
        $this->SetFont('Arial', 'B', 8);
        $this->Cell($ancho / 3, 5, 'PREPARADO POR', 'T', 0, 'C');
        $this->Cell($ancho / 3, 5, 'REVISADO POR', 'T', 0, 'C');
        $this->Cell($ancho / 3, 5, 'AUTORIZADO POR', 'T', 1, 'C');
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, mb_convert_encoding("Página {$this->PageNo()}/", 'ISO-8859-1', 'UTF-8') . '{nb}', 0, 0, 'R');
    }

    /**
     * Renderiza el Mayor Analítico completo.
     * $datos debe ser el resultado de MovimientoPresupuestarioRepository::getMayorAnalitico()
     */
    public function generarMayorAnalitico(array $datos, ?string $filtroEstruc = null): void
    {
        $this->AddPage();
        $this->SetFont('Arial', '', 8);

        // Anchos de columnas (total ~277mm en landscape A4)
        $w = [50, 22, 60, 22, 22, 22, 22, 22, 22, 17];
        // Cabeceras
        $cabecera = [
            'Estructura / Cuenta',
            'Comprobante',
            mb_convert_encoding('Denominación', 'ISO-8859-1', 'UTF-8'),
            'Fecha',
            'Tipo Op.',
            mb_convert_encoding('Asignado', 'ISO-8859-1', 'UTF-8'),
            mb_convert_encoding('Crédito (CA)', 'ISO-8859-1', 'UTF-8'),
            'Gasto (CG)',
            'Traspaso (TR)',
            'Disponible',
        ];

        $ep_anterior  = null;
        $cpu_anterior = null;

        foreach ($datos as $grupo) {
            $ep  = $grupo['descripcion_ep'];
            $cpu = $grupo['codigo_plan_unico'] . ' - ' . $grupo['denominacion_cuenta'];

            // Encabezado de Estructura
            if ($ep !== $ep_anterior) {
                if ($ep_anterior !== null) {
                    $this->Ln(3);
                }
                $this->SetFillColor(0, 86, 179);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(array_sum($w), 7,
                    mb_convert_encoding("ESTRUCTURA: $ep", 'ISO-8859-1', 'UTF-8'),
                    1, 1, 'L', true
                );
                $this->SetTextColor(0);

                // Imprimir cabecera de columnas
                $this->SetFillColor(33, 37, 41);
                $this->SetTextColor(255);
                $this->SetFont('Arial', 'B', 7);
                foreach ($cabecera as $i => $col) {
                    $this->Cell($w[$i], 6, $col, 1, 0, 'C', true);
                }
                $this->Ln();
                $this->SetTextColor(0);
                $ep_anterior  = $ep;
                $cpu_anterior = null;
            }

            // Encabezado de cuenta (sub-agrupación)
            if ($cpu !== $cpu_anterior) {
                $this->SetFillColor(220, 235, 250);
                $this->SetFont('Arial', 'B', 7);
                $this->Cell(array_sum($w), 5,
                    mb_convert_encoding("  Partida: $cpu", 'ISO-8859-1', 'UTF-8'),
                    'LB', 1, 'L', true
                );
                $cpu_anterior = $cpu;
            }

            // Líneas de movimiento
            $this->SetFillColor(240, 240, 240);
            $this->SetFont('Arial', '', 7);
            $fill = false;

            foreach ($grupo['movimientos'] as $mov) {
                $tipo = $mov['id_operacion'];
                $aap  = $tipo === 'AAP' ? number_format((float)$mov['monto_mp'], 2, ',', '.') : '';
                $ca   = $tipo === 'CA'  ? number_format((float)$mov['monto_mp'], 2, ',', '.') : '';
                $cg   = $tipo === 'CG'  ? number_format((float)$mov['monto_mp'], 2, ',', '.') : '';
                $tr   = $tipo === 'TR'  ? number_format((float)$mov['monto_mp'], 2, ',', '.') : '';

                $this->Cell($w[0], 5, '', 'LR', 0, 'L', $fill);
                $this->Cell($w[1], 5, mb_convert_encoding($mov['numero_c'] ?? '', 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'C', $fill);
                $this->Cell($w[2], 5, mb_convert_encoding(substr($mov['denominacion_c'] ?? '', 0, 35), 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'L', $fill);
                $this->Cell($w[3], 5, $mov['fecha_c'] ?? '', 'LR', 0, 'C', $fill);
                $this->Cell($w[4], 5, $tipo, 'LR', 0, 'C', $fill);
                $this->Cell($w[5], 5, $aap, 'LR', 0, 'R', $fill);
                $this->Cell($w[6], 5, $ca,  'LR', 0, 'R', $fill);
                $this->Cell($w[7], 5, $cg,  'LR', 0, 'R', $fill);
                $this->Cell($w[8], 5, $tr,  'LR', 0, 'R', $fill);
                $this->Cell($w[9], 5, number_format((float)$mov['saldo_acumulado'], 2, ',', '.'), 'LR', 0, 'R', $fill);
                $this->Ln();
                $fill = !$fill;
            }

            // Totales por Cuenta
            $this->SetFont('Arial', 'B', 7);
            $this->SetFillColor(200, 220, 255);
            $totalDisp = (float)$grupo['disponible'];
            $colorDisp = $totalDisp >= 0;

            $this->Cell($w[0], 5, mb_convert_encoding('TOTAL PARTIDA', 'ISO-8859-1', 'UTF-8'), 'LTB', 0, 'R', true);
            $this->Cell($w[1], 5, '', 'TB', 0, 'C', true);
            $this->Cell($w[2], 5, '', 'TB', 0, 'L', true);
            $this->Cell($w[3], 5, '', 'TB', 0, 'C', true);
            $this->Cell($w[4], 5, '', 'TB', 0, 'C', true);
            $this->Cell($w[5], 5, number_format((float)$grupo['asignado_inicial'], 2, ',', '.'),    'TB', 0, 'R', true);
            $this->Cell($w[6], 5, number_format((float)$grupo['creditos_adicionales'], 2, ',', '.'), 'TB', 0, 'R', true);
            $this->Cell($w[7], 5, number_format((float)$grupo['gastos_causados'], 2, ',', '.'),      'TB', 0, 'R', true);
            $this->Cell($w[8], 5, number_format((float)$grupo['traspasos_reduccion'], 2, ',', '.'),  'TB', 0, 'R', true);
            $this->Cell($w[9], 5, number_format($totalDisp, 2, ',', '.'), 'TBR', 0, 'R', true);
            $this->Ln(7);
        }
    }
}
