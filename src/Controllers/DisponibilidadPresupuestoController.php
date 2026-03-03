<?php

namespace App\Controllers;

use App\Repositories\MovimientoPresupuestarioRepository;
use App\Repositories\EstrucPresupuestariaRepository;
use App\Repositories\PlanUnicoCuentasRepository;

class DisponibilidadPresupuestoController extends HomeController
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

    public function index(): void
    {
        $id_estruc = isset($_GET['id_estruc']) && $_GET['id_estruc'] !== '' ? (int)$_GET['id_estruc'] : null;
        $id_cuenta = isset($_GET['id_cuenta']) && $_GET['id_cuenta'] !== '' ? (int)$_GET['id_cuenta'] : null;

        $resumen     = $this->movimientoRepo->getResumenDisponibilidad($id_estruc, $id_cuenta);
        $estructuras = $this->estructurasRepo->all();
        $planCuentas = $this->planUnicoRepo->all();

        // Totales globales del filtro
        $totales = [
            'asignado_inicial'    => array_sum(array_column($resumen, 'asignado_inicial')),
            'creditos_adicionales'=> array_sum(array_column($resumen, 'creditos_adicionales')),
            'gastos_causados'     => array_sum(array_column($resumen, 'gastos_causados')),
            'traspasos_reduccion' => array_sum(array_column($resumen, 'traspasos_reduccion')),
            'disponible'          => array_sum(array_column($resumen, 'disponible')),
        ];

        $this->renderView('disponibilidad_presupuesto/index', [
            'titulo'       => 'Consulta de Disponibilidad Presupuestaria',
            'resumen'      => $resumen,
            'totales'      => $totales,
            'estructuras'  => $estructuras,
            'planCuentas'  => $planCuentas,
            'id_estruc'    => $id_estruc,
            'id_cuenta'    => $id_cuenta,
        ]);
    }
}
