<?php

namespace App\Controllers;

use App\Models\TipoArticulo;
use App\Repositories\TipoArticuloRepository;
use PDOException;

class TiposArticulosController extends HomeController
{
    private TipoArticuloRepository $repo;

    public function __construct(TipoArticuloRepository $repo)
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
        $this->renderView('tipos_articulos/index', [
            'titulo' => 'Tipos de Artículos',
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
        $this->renderView('tipos_articulos/form', [
            'titulo' => $item ? 'Editar Tipo de Artículo' : 'Nuevo Tipo de Artículo',
            'item'   => $item,
            'error'  => $error ?? null,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=tipos_articulos/index');
            exit;
        }

        try {
            $id   = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $item = new TipoArticulo(
                trim($_POST['denominacion'] ?? ''),
                trim($_POST['descripcion']  ?? '') ?: null,
                (int)($_POST['tipo'] ?? 1),
                $id
            );
            $this->repo->save($item);
            header('Location: ?route=tipos_articulos/index');
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
        header('Location: ?route=tipos_articulos/index');
        exit;
    }
}
