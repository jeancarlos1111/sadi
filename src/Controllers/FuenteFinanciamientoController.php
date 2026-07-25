<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

use App\Models\FuenteFinanciamiento;
use App\Repositories\FuenteFinanciamientoRepository;
use PDOException;

class FuenteFinanciamientoController extends BaseController
{
    private FuenteFinanciamientoRepository $repo;

    public function __construct(FuenteFinanciamientoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        Gate::authorize('presupuesto.fuentes.ver');
        $search = $_GET['search'] ?? '';
        $items = [];

        try {
            $page = (int)($_GET['page'] ?? 1);
            $paginator = $this->repo->paginate($search, $page, 15);
            $items = $paginator['data'];
        } catch (PDOException $e) {
            error_log("Error fetching fuente_financiamiento: " . $e->getMessage());
        }

        $this->renderView('presupuesto/fuente_financiamiento/index', [
            'titulo' => 'Fuentes de Financiamiento',
            'items'  => $items,
            'search' => $search,
            'paginator' => $paginator ?? null,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        Gate::authorize($id ? 'presupuesto.fuentes.editar' : 'presupuesto.fuentes.crear');
        $id   = $_GET['id'] ?? null;
        $item = null;

        if ($id) {
            try {
                $item = $this->repo->findById((int)$id);
            } catch (PDOException $e) {
                error_log("Error fetching fuente_financiamiento for form: " . $e->getMessage());
            }
        }

        $this->renderView('presupuesto/fuente_financiamiento/form', [
            'titulo' => $item ? 'Editar Fuente de Financiamiento' : 'Nueva Fuente de Financiamiento',
            'item'   => $item,
        ]);
    }

    public function save(): void
    {
        $id = $_POST['id'] ?? null;
        Gate::authorize($id ? 'presupuesto.fuentes.editar' : 'presupuesto.fuentes.crear');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
                $datosAntes = null;
                if ($id) {
                    $modeloAnterior = $this->repo->findById($id);
                    $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
                }

                $item = new FuenteFinanciamiento(
                    trim($_POST['denominacion'] ?? ''),
                    $id
                );

                $nuevoId = $this->repo->save($item);

                $modeloDespues = $this->repo->findById($nuevoId);
                $this->audit('fuente_financiamiento', $id ? 'EDITAR' : 'CREAR', $nuevoId, $datosAntes, $modeloDespues ? $modeloDespues->toArray() : null);

                header('Location: ?route=fuente_financiamiento/index');
                exit;
            } catch (PDOException $e) {
                error_log("Error saving fuente_financiamiento: " . $e->getMessage());
                // ideally show error
                header('Location: ?route=fuente_financiamiento/index');
                exit;
            }
        }
    }

    public function delete(): void
    {
        Gate::authorize('presupuesto.fuentes.eliminar');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                try {
                    $id = (int)$id;
                    $modeloAnterior = $this->repo->findById($id);
                    $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
                    $this->repo->delete($id);
                    $this->audit('fuente_financiamiento', 'ELIMINAR', $id, $datosAntes, null);
                } catch (PDOException $e) {
                    error_log("Error deleting fuente_financiamiento: " . $e->getMessage());
                }
            }
            header('Location: ?route=fuente_financiamiento/index');
            exit;
        }
    }
}
