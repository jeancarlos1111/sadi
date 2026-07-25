<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

use App\Models\PresupuestoGasto;
use App\Repositories\PresupuestoGastoRepository;
use PDOException;

class PresupuestoController extends HomeController
{
    private PresupuestoGastoRepository $repo;
    private \App\Repositories\FuenteFinanciamientoRepository $fuenteRepo;
    private \App\Repositories\UnidadAdministrativaRepository $unidadRepo;

    public function __construct(PresupuestoGastoRepository $repo, \App\Repositories\FuenteFinanciamientoRepository $fuenteRepo, \App\Repositories\UnidadAdministrativaRepository $unidadRepo)
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
        $this->repo = $repo;
        $this->fuenteRepo = $fuenteRepo;
        $this->unidadRepo = $unidadRepo;
    }

    /** Ejecución Presupuestaria — Vista principal con la tabla de disponibilidades */
    public function index(): void
    {
        Gate::authorize('presupuesto.gastos.ver');
        $search = $_GET['search'] ?? '';

        try {
            $page = (int)($_GET['page'] ?? 1);
            $paginator = $this->repo->paginate($search, $page, 15);
            $partidas = $paginator['data'];
        } catch (PDOException $e) {
            $partidas = [];
            $error = "Error al obtener la ejecución presupuestaria: " . $e->getMessage();
        }

        $this->renderView('presupuesto/ejecucion/index', [
            'titulo'   => 'Ejecución Presupuestaria de Gastos',
            'partidas' => $partidas,
            'search'   => $search,
            'error'    => $error ?? null,
                    'paginator' => $paginator,
        ]);
    }

    /** Formulación — Listado de partidas con opción de crear / editar montos */
    public function formulacion(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $fPartidas    = \Amp\async(fn() => $this->repo->allAsync($search));
            $fEstructuras = \Amp\async(fn() => $this->repo->getEstructurasAsync());
            $fPlanUnico   = \Amp\async(fn() => $this->repo->getPartidasAsync());

            [$partidas, $estructuras, $planUnico] = \Amp\Future\await([$fPartidas, $fEstructuras, $fPlanUnico]);
        } catch (\Throwable $e) {
            $partidas = $estructuras = $planUnico = [];
            $error = "Error asíncrono al cargar formulación: " . $e->getMessage();
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
        Gate::authorize($id ? 'presupuesto.gastos.editar' : 'presupuesto.gastos.crear');
        $id = $_GET['id'] ?? null;
        $presupuesto = null;
        if ($id) {
            $presupuesto = $this->repo->find((int)$id);
        }

        try {
            $fEstructuras = \Amp\async(fn() => $this->repo->getEstructurasAsync());
            $fPlanUnico   = \Amp\async(fn() => $this->repo->getPartidasAsync());
            $fFuentes     = \Amp\async(fn() => $this->fuenteRepo->allAsync());
            $fUnidades    = \Amp\async(fn() => $this->unidadRepo->allAsync());

            [$estructuras, $planUnico, $fuentes, $unidades] = \Amp\Future\await([$fEstructuras, $fPlanUnico, $fFuentes, $fUnidades]);
        } catch (\Throwable $e) {
            error_log("Error asíncrono en Formulario de Presupuesto: " . $e->getMessage());
            $estructuras = [];
            $planUnico = [];
            $fuentes = [];
            $unidades = [];
        }

        $this->renderView('presupuesto/formulacion/form', [
            'titulo'      => $presupuesto ? 'Editar Partida' : 'Formular Partida Presupuestaria',
            'presupuesto' => $presupuesto,
            'partida'     => $presupuesto,
            'estructuras' => $estructuras,
            'planUnico'   => $planUnico,
            'fuentes'     => $fuentes,
            'unidades'    => $unidades,
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
        $idFuenteFinanciamiento = !empty($_POST['id_fuente_financiamiento']) ? (int)$_POST['id_fuente_financiamiento'] : null;
        $idUnidadAdministrativa = !empty($_POST['id_unidad_administrativa']) ? (int)$_POST['id_unidad_administrativa'] : null;

        if (!$idEstructura || !$idPlanUnico || $montoAsignado <= 0) {
            $this->renderView('presupuesto/formulacion/form', [
                'titulo'      => $id ? 'Editar Partida' : 'Nueva Partida',
                'partida'     => null,
                'estructuras' => $this->repo->getEstructuras(),
                'planUnico'   => $this->repo->getPartidas(),
                'fuentes'     => $this->fuenteRepo->all(),
                'unidades'    => $this->unidadRepo->all(),
                'error'       => 'Todos los campos son obligatorios y el monto debe ser mayor a 0.',
            ]);

            return;
        }

        try {
            $datosAntes = null;
            $partida = new PresupuestoGasto($idEstructura, $idPlanUnico, $montoAsignado, 0, 0, 0, $idFuenteFinanciamiento, $idUnidadAdministrativa, $id);
            if ($id) {
                $existing = $this->repo->find($id);
                if ($existing) {
                    $datosAntes = method_exists($existing, 'toArray') ? $existing->toArray() : (array)$existing;
                    $partida = new PresupuestoGasto(
                        $idEstructura,
                        $idPlanUnico,
                        $montoAsignado,
                        $existing->montoComprometido,
                        $existing->montoCausado,
                        $existing->montoPagado,
                        $idFuenteFinanciamiento,
                        $idUnidadAdministrativa,
                        $id
                    );
                }
            }
            $this->repo->save($partida);
            
            // Re-fetch to get correct ID if it was an insert
            // For now, if $id is null we might not easily get it without save returning it. 
            // In PresupuestoGastoRepository save doesn't return ID directly. 
            // We'll log what we have.
            $datosDespues = method_exists($partida, 'toArray') ? $partida->toArray() : (array)$partida;
            $this->audit('presupuesto_gastos', $id ? 'EDITAR' : 'CREAR', $id ?? 0, $datosAntes, $datosDespues);

            header('Location: ?route=presupuesto/formulacion');
            exit;
        } catch (PDOException $e) {
            $this->renderView('presupuesto/formulacion/form', [
                'titulo'      => $id ? 'Editar Partida' : 'Nueva Partida',
                'partida'     => null,
                'estructuras' => $this->repo->getEstructuras(),
                'planUnico'   => $this->repo->getPartidas(),
                'fuentes'     => $this->fuenteRepo->all(),
                'unidades'    => $this->unidadRepo->all(),
                'error'       => 'Error al guardar: ' . $e->getMessage(),
            ]);
        }
    }

    /** POST: eliminar partida (lógico) */
    public function eliminar(): void
    {
        Gate::authorize('presupuesto.gastos.eliminar');
        $id = $_POST['id'] ?? null;
        if ($id) {
            $id = (int)$id;
            $existing = $this->repo->find($id);
            $datosAntes = $existing ? (method_exists($existing, 'toArray') ? $existing->toArray() : (array)$existing) : null;
            $this->repo->delete($id);
            $this->audit('presupuesto_gastos', 'ELIMINAR', $id, $datosAntes, null);
            header('Location: ?route=presupuesto/index&success=Partida+eliminada.');
        }
    }

    public function dashboard(): void
    {
        $start = microtime(true);
        try {
            $pool = \App\Database\AsyncConnection::getPool();

            // Lanzamos consultas concurrentemente
            $fTotal = \Amp\async(fn() => $pool->query("
                SELECT 
                    COALESCE(SUM(monto_asignado), 0) as asignado, 
                    COALESCE(SUM(monto_comprometido), 0) as comprometido,
                    COALESCE(SUM(monto_causado), 0) as causado,
                    COALESCE(SUM(monto_pagado), 0) as pagado
                FROM presupuesto_gastos WHERE eliminado = false
            ")->fetchRow());
            
            $fTopPartidas = \Amp\async(function() use ($pool) {
                $result = $pool->query("
                    SELECT p.codigo_plan_unico as partida_codigo, p.denominacion as partida_nombre, 
                           pg.monto_asignado, pg.monto_comprometido
                    FROM presupuesto_gastos pg
                    JOIN plan_unico_cuentas p ON pg.id_codigo_plan_unico = p.id_codigo_plan_unico
                    WHERE pg.eliminado = false 
                    ORDER BY pg.monto_asignado DESC LIMIT 5
                ");
                $rows = [];
                foreach ($result as $row) {
                    $rows[] = $row;
                }
                return $rows;
            });

            $fFuentes = \Amp\async(function() use ($pool) {
                $result = $pool->query("
                    SELECT ff.denominacion as nombre, 
                           COALESCE(SUM(pg.monto_asignado), 0) as asignado,
                           COALESCE(SUM(pg.monto_comprometido), 0) as comprometido
                    FROM presupuesto_gastos pg
                    JOIN fuente_financiamiento ff ON pg.id_fuente_financiamiento = ff.id_fuente_financiamiento
                    WHERE pg.eliminado = false
                    GROUP BY ff.denominacion
                ");
                $rows = [];
                foreach ($result as $row) {
                    $rows[] = $row;
                }
                return $rows;
            });

            // Esperamos a que todas terminen al mismo tiempo
            [$totalRow, $topPartidas, $fuentes] = \Amp\Future\await([$fTotal, $fTopPartidas, $fFuentes]);

        } catch (\Throwable $e) {
            $error = "Error al cargar los datos del dashboard: " . $e->getMessage();
            $totalRow = ['asignado' => 0, 'comprometido' => 0, 'causado' => 0, 'pagado' => 0];
            $topPartidas = [];
            $fuentes = [];
        }

        $end = microtime(true);
        $timeTaken = round(($end - $start) * 1000, 2); // ms

        // Calculamos disponibilidad general
        $totalRow['disponible'] = ($totalRow['asignado'] ?? 0) - ($totalRow['comprometido'] ?? 0);

        $this->renderView('presupuesto/dashboard', [
            'titulo' => 'Panel de Control Presupuestario',
            'totalRow' => $totalRow,
            'topPartidas' => $topPartidas,
            'fuentes' => $fuentes,
            'timeTaken' => $timeTaken,
            'error' => $error ?? null,
        ]);
    }
}
