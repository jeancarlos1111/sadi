<?php

namespace App\Controllers;

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
        $search = $_GET['search'] ?? '';

        try {
            $items = $this->repo->all($search);
        } catch (PDOException | \Exception $e) {
            $items = [];
            $error = "Error: " . $e->getMessage();
        }
        $this->renderView('plan_unico_cuentas/index', [
            'titulo' => 'Plan Único de Cuentas Presupuestarias',
            'items'  => $items,
            'search' => $search,
            'error'  => $error ?? null,
        ]);
    }

    public function form(): void
    {
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
            $item = new PlanUnicoCuentas(
                trim($_POST['codigo']       ?? ''),
                trim($_POST['denominacion'] ?? ''),
                $id
            );
            $this->repo->save($item);
            header('Location: ?route=plan_unico_cuentas/index');
            exit;
        } catch (PDOException | \Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
    }

    public function eliminar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->delete((int)$id);
            } catch (PDOException | \Exception $e) {
                die("Error: " . $e->getMessage());
            }
        }
        header('Location: ?route=plan_unico_cuentas/index');
        exit;
    }
}
