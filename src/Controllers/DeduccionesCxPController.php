<?php

namespace App\Controllers;

use App\Models\DeduccionCxP;
use App\Repositories\DeduccionCxPRepository;
use PDOException;

class DeduccionesCxPController extends HomeController
{
    private DeduccionCxPRepository $repo;

    public function __construct(DeduccionCxPRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $items = [];

        try {
            $items = $this->repo->all($search);
        } catch (PDOException $e) {
            // Log the error or handle it appropriately
            error_log("Error fetching deducciones: " . $e->getMessage());
            // Optionally, set a user-friendly error message
            // $this->renderView('error_page', ['message' => 'Error al cargar las deducciones.']);
        }

        $this->renderView('cxp/deducciones/index', [
            'titulo' => 'Deducciones y Retenciones (CxP)',
            'items'  => $items,
            'search' => $search,
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
                error_log("Error fetching deduccion by ID: " . $e->getMessage());
                // Handle error, e.g., redirect or show message
            }
        }

        $this->renderView('cxp/deducciones/form', [
            'titulo' => $item ? 'Editar Deducción' : 'Nueva Deducción',
            'item'   => $item,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=deducciones_cxp/index');
            exit;
        }

        try {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $deduccion = new DeduccionCxP(
                trim($_POST['codigo_deduccion'] ?? ''),
                trim($_POST['denominacion'] ?? ''),
                (float)($_POST['porcentaje'] ?? 0),
                trim($_POST['aplica_sobre'] ?? 'BASE'),
                !empty($_POST['activo']),
                $id
            );
            $this->repo->save($deduccion);
            header('Location: ?route=deducciones_cxp/index&success=Deducción guardada.');
        } catch (\Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
        exit;
    }

    public function eliminar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->delete((int)$id);
                header('Location: ?route=deducciones_cxp/index&success=Deducción+eliminada');
            } catch (\Exception $e) {
                die("Error al eliminar: " . $e->getMessage());
            }
        } else {
            header('Location: ?route=deducciones_cxp/index');
        }
        exit;
    }
}
