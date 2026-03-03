<?php

namespace App\Controllers;

use App\Repositories\PeriodoPresupuestarioRepository;
use App\Models\PeriodoPresupuestario;

class PeriodosPresupuestoController extends HomeController
{
    private PeriodoPresupuestarioRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new PeriodoPresupuestarioRepository();
    }

    /** Muestra la grilla de 12 meses del año con su estado y permite cambiarlos */
    public function index(): void
    {
        $anio = (int)($_GET['anio'] ?? date('Y'));

        $periodos = $this->repo->getByAnio($anio);

        $error   = $_SESSION['error']   ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        $this->renderView('periodos_presupuesto/index', [
            'titulo'   => "Gestión de Períodos Presupuestarios – $anio",
            'periodos' => $periodos,
            'anio'     => $anio,
            'meses'    => PeriodoPresupuestario::MESES,
            'error'    => $error,
            'success'  => $success,
        ]);
    }

    /** Aplica los cambios de estado masivos (cierre/apertura) a los meses del año */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?route=periodos_presupuesto/index");
            exit;
        }

        $anio        = (int)($_POST['anio'] ?? date('Y'));
        $observacion = trim($_POST['observacion'] ?? '');
        // Los checkboxes marcados son los meses que se CIERRAN; los no marcados quedan ABIERTOS
        $mesesCerrados = $_POST['meses_cerrados'] ?? [];

        $estados = [];
        for ($m = 1; $m <= 12; $m++) {
            $estados[$m] = in_array((string)$m, $mesesCerrados) ? 'CERRADO' : 'ABIERTO';
        }

        try {
            $this->repo->actualizarEstados($anio, $estados, $observacion ?: null);
            $_SESSION['success'] = "Períodos del año $anio actualizados correctamente.";
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al guardar: ' . $e->getMessage();
        }

        header("Location: ?route=periodos_presupuesto/index&anio=$anio");
        exit;
    }
}
