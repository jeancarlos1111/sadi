<?php

namespace App\Controllers;

use App\Repositories\MovimientoPresupuestarioRepository;
use App\Repositories\EstrucPresupuestariaRepository;
use App\Repositories\PlanUnicoCuentasRepository;
use App\Services\MayorAnaliticoPdfService;

class MayorAnaliticoController extends HomeController
{
    private MovimientoPresupuestarioRepository $movimientoRepo;
    private EstrucPresupuestariaRepository $estructurasRepo;
    private PlanUnicoCuentasRepository $planUnicoRepo;

    public function __construct()
    {
        parent::__construct();
        $this->movimientoRepo  = new MovimientoPresupuestarioRepository();
        $this->estructurasRepo = new EstrucPresupuestariaRepository();
        $this->planUnicoRepo   = new PlanUnicoCuentasRepository();
    }

    /** Pantalla de parámetros del reporte */
    public function index(): void
    {
        $this->renderView('mayor_analitico/index', [
            'titulo'      => 'Mayor Analítico Presupuestario',
            'estructuras' => $this->estructurasRepo->all(),
            'planCuentas' => $this->planUnicoRepo->all(),
            'anio_actual' => date('Y'),
        ]);
    }

    /** Genera y devuelve el PDF directamente al navegador */
    public function generar(): void
    {
        $id_estruc = isset($_GET['id_estruc']) && $_GET['id_estruc'] !== '' ? (int)$_GET['id_estruc'] : null;
        $id_cuenta = isset($_GET['id_cuenta']) && $_GET['id_cuenta'] !== '' ? (int)$_GET['id_cuenta'] : null;
        $anio      = !empty($_GET['anio']) ? $_GET['anio'] : date('Y');

        $datos = $this->movimientoRepo->getMayorAnalitico($id_estruc, $id_cuenta, $anio);

        // Limpiar CUALQUIER buffer de salida activo (el layout HTML no debe interferir)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Si no hay datos, mostrar PDF informativo en lugar de uno vacío
        if (empty($datos)) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h2 style="font-family:sans-serif;color:#dc3545;">Sin movimientos presupuestarios</h2>';
            echo '<p style="font-family:sans-serif;">No existen movimientos registrados para los filtros seleccionados (Año: ' . htmlspecialchars($anio) . ').</p>';
            echo '<p><a href="javascript:history.back()">← Volver</a></p>';
            exit;
        }

        $pdf = new MayorAnaliticoPdfService($anio);
        $pdf->generarMayorAnalitico($datos);

        $sufijo  = $id_estruc ? "_EP{$id_estruc}" : '_TODAS_EP';
        $nombre  = "mayor_analitico_{$anio}{$sufijo}.pdf";

        // 'I' = mostrar inline en el browser (permite ver en pestaña y descargar)
        $pdf->Output('I', $nombre);
        exit;
    }
}
