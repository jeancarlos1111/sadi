<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UnidadAdministrativa;
use App\Repositories\UnidadAdministrativaRepository;
use PDOException;

class UnidadesAdministrativasController extends HomeController
{
    private UnidadAdministrativaRepository $repo;

    public function __construct(UnidadAdministrativaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $page = (int)($_GET['page'] ?? 1);
            $paginator = $this->repo->paginate($search, $page, 15);
            $items = $paginator['data'];
        } catch (PDOException | \Exception $e) {
            $items = [];
            $error = "Error: " . $e->getMessage();
        }
        $this->renderView('unidades_administrativas/index', [
            'titulo' => 'Unidades Administrativas',
            'items'  => $items,
            'search' => $search,
            'error'  => $error ?? null,
                    'paginator' => $paginator,
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
        $this->renderView('unidades_administrativas/form', [
            'titulo' => $item ? 'Editar Unidad Administrativa' : 'Nueva Unidad Administrativa',
            'item'   => $item,
            'error'  => $error ?? null,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=unidades_administrativas/index');
            exit;
        }

        try {
            $id   = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $datosAntes = null;
            if ($id) {
                $modeloAnterior = $this->repo->findById($id);
                $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
            }

            $item = new UnidadAdministrativa(
                trim($_POST['codigo']       ?? ''),
                trim($_POST['denominacion'] ?? ''),
                $id
            );
            $nuevoId = $this->repo->save($item);

            $modeloDespues = $this->repo->findById($nuevoId);
            $this->audit('unidad_administrativa', $id ? 'EDITAR' : 'CREAR', $nuevoId, $datosAntes, $modeloDespues ? $modeloDespues->toArray() : null);

            header('Location: ?route=unidades_administrativas/index');
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
                $id = (int)$id;
                $modeloAnterior = $this->repo->findById($id);
                $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
                $this->repo->delete($id);
                $this->audit('unidad_administrativa', 'ELIMINAR', $id, $datosAntes, null);
            } catch (PDOException | \Exception $e) {
                die("Error: " . $e->getMessage());
            }
        }
        header('Location: ?route=unidades_administrativas/index');
        exit;
    }
}
