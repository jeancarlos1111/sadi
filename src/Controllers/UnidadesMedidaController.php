<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

use App\Models\UnidadMedida;
use App\Repositories\UnidadMedidaRepository;
use PDOException;

class UnidadesMedidaController extends HomeController
{
    private UnidadMedidaRepository $repo;

    public function __construct(UnidadMedidaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        Gate::authorize('inventario.unidades_medida.ver');
        $search = $_GET['search'] ?? '';

        try {
            $page = (int)($_GET['page'] ?? 1);
            $paginator = $this->repo->paginate($search, $page, 15);
            $items = $paginator['data'];
        } catch (PDOException | \Exception $e) {
            $items = [];
            $error = "Error: " . $e->getMessage();
        }
        $this->renderView('unidades_medida/index', [
            'titulo' => 'Unidades de Medida',
            'items'  => $items,
            'search' => $search,
            'error'  => $error ?? null,
                    'paginator' => $paginator,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        Gate::authorize($id ? 'inventario.unidades_medida.editar' : 'inventario.unidades_medida.crear');
        $id    = $_GET['id'] ?? null;
        $item  = null;
        $error = null;

        try {
            if ($id) {
                $item = $this->repo->findById((int)$id);
            }
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
        }
        $this->renderView('unidades_medida/form', [
            'titulo' => $item ? 'Editar Unidad de Medida' : 'Nueva Unidad de Medida',
            'item'   => $item,
            'error'  => $error,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=unidades_medida/index');
            exit;
        }

        try {
            $id   = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $item = new UnidadMedida(
                trim($_POST['denominacion'] ?? ''),
                trim($_POST['unidades']     ?? ''),
                trim($_POST['observacion']  ?? '') ?: null,
                $id
            );
            $this->repo->save($item);
            header('Location: ?route=unidades_medida/index');
            exit;
        } catch (PDOException | \Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
    }

    public function eliminar(): void
    {
        Gate::authorize('inventario.unidades_medida.eliminar');
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->delete((int)$id);
            } catch (PDOException | \Exception $e) {
                die("Error: " . $e->getMessage());
            }
        }
        header('Location: ?route=unidades_medida/index');
        exit;
    }
}
