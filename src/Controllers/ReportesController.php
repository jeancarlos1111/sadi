<?php

namespace App\Controllers;

use App\Repositories\ReportesOnapreRepository;

class ReportesController extends BaseController
{
    private ReportesOnapreRepository $repo;

    public function __construct(ReportesOnapreRepository $repo)
    {
        $this->repo = $repo;

        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function estadoResultados(): void
    {
        $anio = $_GET['anio'] ?? date('Y');
        $mes = $_GET['mes'] ?? date('m');

        $filas = $this->repo->getEstadoResultados($anio, $mes);

        $totalIngresos = 0;
        $totalGastos = 0;

        foreach ($filas as $f) {
            if ($f['tipo_cuenta'] === 'INGRESO') {
                $totalIngresos += $f['saldo'];
            }
            if ($f['tipo_cuenta'] === 'EGRESO') {
                $totalGastos += $f['saldo'];
            }
        }

        $resultadoDelEjercicio = $totalIngresos - $totalGastos;

        $this->renderView('reportes/estado_resultados', [
            'titulo' => 'Estado de Resultados (ONAPRE)',
            'filas' => $filas,
            'anio' => $anio,
            'mes' => $mes,
            'totalIngresos' => $totalIngresos,
            'totalGastos' => $totalGastos,
            'resultadoDelEjercicio' => $resultadoDelEjercicio,
        ]);
    }

    public function balanceGeneral(): void
    {
        $hastaFecha = $_GET['hasta_fecha'] ?? date('Y-m-d');

        $filas = $this->repo->getBalanceGeneral($hastaFecha);

        // El resultado del ejercicio lo calculamos también para sumarlo al Patrimonio y cuadrar el Balance Total (Ecuación Patrimonial)
        $resultadoAct = $this->repo->getEstadoResultados(date('Y', strtotime($hastaFecha)), ''); // Todo el año
        $utilidadEjercicio = 0;
        foreach ($resultadoAct as $r) {
            if ($r['tipo_cuenta'] === 'INGRESO') {
                $utilidadEjercicio += $r['saldo'];
            }
            if ($r['tipo_cuenta'] === 'EGRESO') {
                $utilidadEjercicio -= $r['saldo'];
            }
        }

        $totalActivo = 0;
        $totalPasivo = 0;
        $totalPatrimonioHist = 0;

        foreach ($filas as $f) {
            if ($f['tipo_cuenta'] === 'ACTIVO') {
                $totalActivo += $f['saldo'];
            }
            if ($f['tipo_cuenta'] === 'PASIVO') {
                $totalPasivo += $f['saldo'];
            }
            if ($f['tipo_cuenta'] === 'PATRIMONIO') {
                $totalPatrimonioHist += $f['saldo'];
            }
        }

        $totalPatrimonioReal = $totalPatrimonioHist + $utilidadEjercicio;
        $totalPasivoPatrimonio = $totalPasivo + $totalPatrimonioReal;

        $this->renderView('reportes/balance_general', [
            'titulo' => 'Balance General (ONAPRE)',
            'filas' => $filas,
            'hastaFecha' => $hastaFecha,
            'totalActivo' => $totalActivo,
            'totalPasivo' => $totalPasivo,
            'totalPatrimonioHist' => $totalPatrimonioHist,
            'utilidadEjercicio' => $utilidadEjercicio,
            'totalPatrimonioReal' => $totalPatrimonioReal,
            'totalPasivoPatrimonio' => $totalPasivoPatrimonio,
            'cuadrado' => abs($totalActivo - $totalPasivoPatrimonio) < 0.01, // Tolerancia decímal
        ]);
    }
}
