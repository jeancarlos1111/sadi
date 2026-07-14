<?php

declare(strict_types=1);

namespace App\Controllers;

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
        if (!isset($_SESSION['usuario'])) {
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
            $partida = new PresupuestoGasto($idEstructura, $idPlanUnico, $montoAsignado, 0, 0, 0, $idFuenteFinanciamiento, $idUnidadAdministrativa, $id);
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
                        $idFuenteFinanciamiento,
                        $idUnidadAdministrativa,
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
                'fuentes'     => $this->fuenteRepo->all(),
                'unidades'    => $this->unidadRepo->all(),
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

    /** Dashboard PoC con AMPHP Fibers */
    public function dashboard(): void
    {
        $start = microtime(true);
        try {
            $pool = \App\Database\AsyncConnection::getPool();

            // Lanzamos consultas concurrentemente
            $fTotal = \Amp\async(fn() => $pool->query("SELECT COALESCE(SUM(monto_asignado), 0) as total_asignado, COALESCE(SUM(monto_comprometido), 0) as total_comprometido FROM presupuesto_gastos WHERE eliminado = false")->fetchRow());
            
            $fTopPartidas = \Amp\async(function() use ($pool) {
                $result = $pool->query("SELECT p.codigo_plan_unico as partida_codigo, p.denominacion as partida_nombre, pg.monto_asignado 
                                        FROM presupuesto_gastos pg
                                        JOIN plan_unico_cuentas p ON pg.id_codigo_plan_unico = p.id_codigo_plan_unico
                                        WHERE pg.eliminado = false 
                                        ORDER BY pg.monto_asignado DESC LIMIT 5");
                $rows = [];
                foreach ($result as $row) {
                    $rows[] = $row;
                }
                return $rows;
            });

            $fFuentes = \Amp\async(function() use ($pool) {
                $result = $pool->query("
                    SELECT ff.denominacion as nombre, COALESCE(SUM(pg.monto_asignado), 0) as total
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
            $error = "Error asíncrono: " . $e->getMessage();
            $totalRow = ['total_asignado' => 0, 'total_comprometido' => 0];
            $topPartidas = [];
            $fuentes = [];
        }

        $end = microtime(true);
        $timeTaken = round(($end - $start) * 1000, 2); // ms

        $this->renderView('presupuesto/dashboard', [
            'titulo' => 'Dashboard Presupuestario (Fibers PoC)',
            'totalRow' => $totalRow ?? null,
            'topPartidas' => $topPartidas ?? [],
            'fuentes' => $fuentes ?? [],
            'timeTaken' => $timeTaken,
            'error' => $error ?? null,
        ]);
    }
}
