<?php

namespace App\Controllers;

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
        $search = $_GET['search'] ?? '';

        try {
            $items = $this->repo->all($search);
        } catch (PDOException | \Exception $e) {
            $items = [];
            $error = "Error: " . $e->getMessage();
        }
        $this->renderView('unidades_medida/index', [
            'titulo' => 'Unidades de Medida',
            'items'  => $items,
            'search' => $search,
            'error'  => $error ?? null,
        ]);
    }

    public function form(): void
    {
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
