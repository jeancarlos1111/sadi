<?php

declare(strict_types=1);

namespace App\Controllers;

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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $item = new FuenteFinanciamiento(
                    trim($_POST['denominacion'] ?? ''),
                    !empty($_POST['id']) ? (int)$_POST['id'] : null
                );

                $this->repo->save($item);

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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                try {
                    $this->repo->delete((int)$id);
                } catch (PDOException $e) {
                    error_log("Error deleting fuente_financiamiento: " . $e->getMessage());
                }
            }
            header('Location: ?route=fuente_financiamiento/index');
            exit;
        }
    }
}
