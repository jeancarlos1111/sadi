<?php

namespace App\Controllers;

use App\Models\PresupuestoGasto;
use App\Repositories\PresupuestoGastoRepository;
use PDOException;

class PresupuestoController extends HomeController
{
    private PresupuestoGastoRepository $repo;

    public function __construct(PresupuestoGastoRepository $repo)
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
        $this->repo = $repo;
    }

    /** Ejecución Presupuestaria — Vista principal con la tabla de disponibilidades */
    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $partidas = $this->repo->all($search);
        } catch (PDOException $e) {
            $partidas = [];
            $error = "Error al obtener la ejecución presupuestaria: " . $e->getMessage();
        }

        $this->renderView('presupuesto/ejecucion/index', [
            'titulo'   => 'Ejecución Presupuestaria de Gastos',
            'partidas' => $partidas,
            'search'   => $search,
            'error'    => $error ?? null,
        ]);
    }

    /** Formulación — Listado de partidas con opción de crear / editar montos */
    public function formulacion(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $partidas    = $this->repo->all($search);
            $estructuras = $this->repo->getEstructuras();
            $planUnico   = $this->repo->getPartidas();
        } catch (PDOException $e) {
            $partidas = $estructuras = $planUnico = [];
            $error = "Error al cargar formulación: " . $e->getMessage();
        }

        $this->renderView('presupuesto/formulacion/index', [
            'titulo'      => 'Formulación del Presupuesto de Gastos',
            'partidas'    => $partidas,
            'estructuras' => $estructuras,
            'planUnico'   => $planUnico,
            'search'      => $search,
            'error'       => $error ?? null,
        ]);
    }

    /** Formulario para crear o editar una partida presupuestaria */
    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        $presupuesto = null;
        if ($id) {
            $presupuesto = $this->repo->find((int)$id);
        }

        $this->renderView('presupuesto/formulacion/form', [
            'titulo'      => $presupuesto ? 'Editar Partida' : 'Formular Partida Presupuestaria',
            'presupuesto' => $presupuesto,
            'partida'     => $presupuesto,
            'estructuras' => $this->repo->getEstructuras(),
            'planUnico'   => $this->repo->getPartidas(),
        ]);
    }

    /** POST: guardar nueva partida o actualizar monto asignado */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=presupuesto/formulacion');
            exit;
        }

        $id           = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $idEstructura = (int)($_POST['id_estruc_presupuestaria'] ?? 0);
        $idPlanUnico  = (int)($_POST['id_codigo_plan_unico'] ?? 0);
        $montoAsignado = (float)str_replace(',', '.', $_POST['monto_asignado'] ?? '0');

        if (!$idEstructura || !$idPlanUnico || $montoAsignado <= 0) {
            $this->renderView('presupuesto/formulacion/form', [
                'titulo'      => $id ? 'Editar Partida' : 'Nueva Partida',
                'partida'     => null,
                'estructuras' => $this->repo->getEstructuras(),
                'planUnico'   => $this->repo->getPartidas(),
                'error'       => 'Todos los campos son obligatorios y el monto debe ser mayor a 0.',
            ]);

            return;
        }

        try {
            $partida = new PresupuestoGasto($idEstructura, $idPlanUnico, $montoAsignado, 0, 0, 0, $id);
            if ($id) {
                $existing = $this->repo->find($id);
                if ($existing) {
                    $partida = new PresupuestoGasto(
                        $idEstructura,
                        $idPlanUnico,
                        $montoAsignado,
                        $existing->montoComprometido,
                        $existing->montoCausado,
                        $existing->montoPagado,
                        $id
                    );
                }
            }
            $this->repo->save($partida);
            header('Location: ?route=presupuesto/formulacion');
            exit;
        } catch (PDOException $e) {
            $this->renderView('presupuesto/formulacion/form', [
                'titulo'      => $id ? 'Editar Partida' : 'Nueva Partida',
                'partida'     => null,
                'estructuras' => $this->repo->getEstructuras(),
                'planUnico'   => $this->repo->getPartidas(),
                'error'       => 'Error al guardar: ' . $e->getMessage(),
            ]);
        }
    }

    /** POST: eliminar partida (lógico) */
    public function eliminar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->repo->delete((int)$id);
            header('Location: ?route=presupuesto/index&success=Partida+eliminada.');
        }
    }
}
