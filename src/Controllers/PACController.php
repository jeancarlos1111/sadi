<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PAC;
use App\Repositories\AccionCentralizadaRepository;
use App\Repositories\ArticuloRepository;
use App\Repositories\PACRepository;
use App\Repositories\ProyectoRepository;
use PDOException;

class PACController extends BaseController
{
    private PACRepository $repo;
    private ArticuloRepository $artRepo;
    private ProyectoRepository $proyRepo;
    private AccionCentralizadaRepository $acRepo;
    private \App\Repositories\PresupuestoGastoRepository $pgRepo;

    public function __construct(
        PACRepository $repo,
        ArticuloRepository $artRepo,
        ProyectoRepository $proyRepo,
        AccionCentralizadaRepository $acRepo,
        \App\Repositories\PresupuestoGastoRepository $pgRepo
    ) {
        $this->repo = $repo;
        $this->artRepo = $artRepo;
        $this->proyRepo = $proyRepo;
        $this->acRepo = $acRepo;
        $this->pgRepo = $pgRepo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $items = [];

        try {
            $page = (int)($_GET['page'] ?? 1);
            $paginator = $this->repo->paginate($search, $page, 15);
            $items = $paginator['data'];
        } catch (PDOException $e) {
            error_log("Error fetching PAC: " . $e->getMessage());
        }

        $this->renderView('presupuesto/pac/index', [
            'titulo' => 'Programación Anual de Compras (PAC)',
            'items'  => $items,
            'search' => $search,
            'paginator' => $paginator ?? null,
        ]);
    }

    public function form(): void
    {
        $id   = $_GET['id'] ?? null;
        $item = null;

        try {
            if ($id) {
                $item = $this->repo->findById((int)$id);
            }
            
            $fArticulos = \Amp\async(fn() => $this->artRepo->allAsync());
            $fProyectos = \Amp\async(fn() => $this->proyRepo->allAsync());
            $fAcciones  = \Amp\async(fn() => $this->acRepo->allAsync());

            [$articulos, $proyectos, $acciones] = \Amp\Future\await([$fArticulos, $fProyectos, $fAcciones]);
        } catch (\Throwable $e) {
            error_log("Error asíncrono fetching PAC or dependencies: " . $e->getMessage());
            $articulos = [];
            $proyectos = [];
            $acciones = [];
        }

        $this->renderView('presupuesto/pac/form', [
            'titulo'    => $item ? 'Editar Renglón PAC' : 'Nuevo Renglón PAC',
            'item'      => $item,
            'articulos' => $articulos,
            'proyectos' => $proyectos,
            'acciones'  => $acciones,
        ]);
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $item = new PAC(
                    !empty($_POST['id_proyecto']) ? (int)$_POST['id_proyecto'] : null,
                    !empty($_POST['id_accion_centralizada']) ? (int)$_POST['id_accion_centralizada'] : null,
                    (int)$_POST['id_articulo'],
                    (float)($_POST['cantidad_anual'] ?? 0),
                    (float)($_POST['trim_1'] ?? 0),
                    (float)($_POST['trim_2'] ?? 0),
                    (float)($_POST['trim_3'] ?? 0),
                    (float)($_POST['trim_4'] ?? 0),
                    (float)($_POST['costo_estimado'] ?? 0),
                    'PLANIFICADO',
                    !empty($_POST['id']) ? (int)$_POST['id'] : null
                );

                $this->repo->save($item);

                header('Location: ?route=p_a_c/index');
                exit;
            } catch (PDOException $e) {
                error_log("Error saving PAC: " . $e->getMessage());
                header('Location: ?route=p_a_c/index');
                exit;
            }
        }
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                try {
                    $this->repo->delete((int)$id);
                } catch (PDOException $e) {
                    error_log("Error deleting PAC: " . $e->getMessage());
                }
            }
            header('Location: ?route=p_a_c/index');
            exit;
        }
    }

    public function aprobar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                try {
                    $pac = $this->repo->findById((int)$id);
                    if ($pac && $pac->estatus !== 'APROBADO') {
                        $this->repo->aprobar((int)$id);

                        // Buscar el articulo para ver su plan unico
                        $articulo = $this->artRepo->findById($pac->id_articulo);
                        if ($articulo && $articulo->idCodigoPlanUnico) {
                            // Buscar la partida presupuestaria que corresponda
                            // Para MVP, tomamos la primera que coincida con el plan unico
                            $partidas = $this->pgRepo->all('');
                            foreach ($partidas as $p) {
                                if ($p['entity']->idPlanUnico === $articulo->idCodigoPlanUnico) {
                                    $monto_pre = $pac->cantidad_anual * $pac->costo_estimado;
                                    $pg = $p['entity'];
                                    // Update el precompromiso
                                    $pg->montoPrecomprometido += $monto_pre;
                                    $this->pgRepo->save($pg);

                                    break;
                                }
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Error aprobando PAC: " . $e->getMessage());
                }
            }
            header('Location: ?route=p_a_c/index');
            exit;
        }
    }
}
