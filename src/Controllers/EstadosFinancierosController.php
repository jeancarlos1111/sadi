<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\EstadoFinancieroRepository;

class EstadosFinancierosController extends BaseController
{
    private EstadoFinancieroRepository $repo;

    public function __construct()
    {
        if (!isset($_SESSION['usuario'])) {
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

        $this->renderView('contabilidad/reportes/balance_comprobacion', [
            'titulo' => 'Balance de Comprobación',
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'resultados' => $resultados,
        ]);
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
