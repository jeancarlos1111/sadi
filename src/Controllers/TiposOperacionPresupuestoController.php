<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

use App\Models\TipoOperacionPresupuesto;
use App\Repositories\TipoOperacionPresupuestoRepository;
use PDOException;

class TiposOperacionPresupuestoController extends HomeController
{
    private TipoOperacionPresupuestoRepository $repo;

    public function __construct(TipoOperacionPresupuestoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        Gate::authorize('presupuesto.operaciones.ver');
        $search = $_GET['search'] ?? '';

        try {
            $page = (int)($_GET['page'] ?? 1);
            $paginator = $this->repo->paginate($search, $page, 15);
            $items = $paginator['data'];
        } catch (PDOException | \Exception $e) {
            $items = [];
            $error = "Error: " . $e->getMessage();
        }
        $this->renderView('tipos_operacion_presupuesto/index', [
            'titulo' => 'Tipos de Operación Presupuestaria',
            'items'  => $items,
            'search' => $search,
            'error'  => $error ?? null,
                    'paginator' => $paginator,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        Gate::authorize($id ? 'presupuesto.operaciones.editar' : 'presupuesto.operaciones.crear');
        $id = $_GET['id'] ?? null;
        $item = null;

        try {
            if ($id) {
                $item = $this->repo->findById((int)$id);
            }
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
        }
        $this->renderView('tipos_operacion_presupuesto/form', [
            'titulo' => $item ? 'Editar Tipo de Operación' : 'Nuevo Tipo de Operación Presupuestaria',
            'item'   => $item,
            'error'  => $error ?? null,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=tipos_operacion_presupuesto/index');
            exit;
        }

        try {
            $id   = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $datosAntes = null;
            if ($id) {
                $modeloAnterior = $this->repo->findById($id);
                $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
            }

            $item = new TipoOperacionPresupuesto(
                trim($_POST['denominacion'] ?? ''),
                trim($_POST['descripcion']  ?? '') ?: null,
                $id
            );
            $nuevoId = $this->repo->save($item);

            $modeloDespues = $this->repo->findById($nuevoId);
            $this->audit('tipo_operacion_presupuesto', $id ? 'EDITAR' : 'CREAR', $nuevoId, $datosAntes, $modeloDespues ? $modeloDespues->toArray() : null);

            header('Location: ?route=tipos_operacion_presupuesto/index');
            exit;
        } catch (PDOException | \Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
    }

    public function eliminar(): void
    {
        Gate::authorize('presupuesto.operaciones.eliminar');
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $id = (int)$id;
                $modeloAnterior = $this->repo->findById($id);
                $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
                $this->repo->delete($id);
                $this->audit('tipo_operacion_presupuesto', 'ELIMINAR', $id, $datosAntes, null);
            } catch (PDOException | \Exception $e) {
                die("Error: " . $e->getMessage());
            }
        }
        header('Location: ?route=tipos_operacion_presupuesto/index');
        exit;
    }
}
