<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

use App\Models\PlanUnicoCuentas;
use App\Repositories\PlanUnicoCuentasRepository;
use PDOException;

class PlanUnicoCuentasController extends HomeController
{
    private PlanUnicoCuentasRepository $repo;

    public function __construct(PlanUnicoCuentasRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        Gate::authorize('presupuesto.plan_cuentas.ver');
        $search = $_GET['search'] ?? '';

        try {
            $page = (int)($_GET['page'] ?? 1);
            $paginator = $this->repo->paginate($search, $page, 15);
            $items = $paginator['data'];
        } catch (PDOException | \Exception $e) {
            $items = [];
            $error = "Error: " . $e->getMessage();
        }
        $this->renderView('plan_unico_cuentas/index', [
            'titulo' => 'Plan Único de Cuentas Presupuestarias',
            'items'  => $items,
            'search' => $search,
            'error'  => $error ?? null,
                    'paginator' => $paginator,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        Gate::authorize($id ? 'presupuesto.plan_cuentas.editar' : 'presupuesto.plan_cuentas.crear');
        $id = $_GET['id'] ?? null;
        $item = null;

        try {
            if ($id) {
                $item = $this->repo->findById((int)$id);
            }
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
        }
        $this->renderView('plan_unico_cuentas/form', [
            'titulo' => $item ? 'Editar Cuenta' : 'Nueva Cuenta Presupuestaria',
            'item'   => $item,
            'error'  => $error ?? null,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=plan_unico_cuentas/index');
            exit;
        }

        try {
            $id   = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $datosAntes = null;
            if ($id) {
                $modeloAnterior = $this->repo->findById($id);
                $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
            }

            $item = new PlanUnicoCuentas(
                trim($_POST['codigo']       ?? ''),
                trim($_POST['denominacion'] ?? ''),
                $id
            );
            $nuevoId = $this->repo->save($item);

            $modeloDespues = $this->repo->findById($nuevoId);
            $this->audit('plan_unico_cuentas', $id ? 'EDITAR' : 'CREAR', $nuevoId, $datosAntes, $modeloDespues ? $modeloDespues->toArray() : null);

            header('Location: ?route=plan_unico_cuentas/index');
            exit;
        } catch (PDOException | \Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
    }

    public function eliminar(): void
    {
        Gate::authorize('presupuesto.plan_cuentas.eliminar');
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $id = (int)$id;
                $modeloAnterior = $this->repo->findById($id);
                $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
                $this->repo->delete($id);
                $this->audit('plan_unico_cuentas', 'ELIMINAR', $id, $datosAntes, null);
            } catch (PDOException | \Exception $e) {
                die("Error: " . $e->getMessage());
            }
        }
        header('Location: ?route=plan_unico_cuentas/index');
        exit;
    }
}
