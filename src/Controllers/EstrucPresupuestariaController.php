<?php

namespace App\Controllers;

use App\Models\EstructuraPresupuestaria;
use App\Repositories\EstrucPresupuestariaRepository;
use Exception;
use PDOException;

class EstrucPresupuestariaController extends HomeController
{
    private EstrucPresupuestariaRepository $repo;

    public function __construct(EstrucPresupuestariaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $estructuras = [];

        try {
            $estructuras = $this->repo->all();
        } catch (PDOException $e) {
            error_log("Error fetching estructuras: " . $e->getMessage());
        }

        $this->renderView('presupuesto/estructuras/index', [
            'titulo'      => 'Estructuras Presupuestarias',
            'estructuras' => $estructuras,
            'search'      => $search,
        ]);
    }

    public function form(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $estructura = $id ? $this->repo->find($id) : null;

        $this->renderView('presupuesto/estructuras/form', [
            'titulo'     => $estructura ? 'Editar Estructura Presupuestaria' : 'Nueva Estructura Presupuestaria',
            'estructura' => $estructura,
            'error'      => null,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=estruc_presupuestaria/index');
            exit;
        }

        $id          = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $descripcion = trim($_POST['descripcion_ep'] ?? '');

        if ($descripcion === '') {
            $this->renderView('presupuesto/estructuras/form', [
                'titulo'     => $id ? 'Editar Estructura' : 'Nueva Estructura',
                'estructura' => null,
                'error'      => 'La descripción es obligatoria.',
            ]);

            return;
        }

        try {
            $ep = new EstructuraPresupuestaria($id ?: 0, $descripcion);
            $this->repo->save($ep);
            header('Location: ?route=estruc_presupuestaria/index&success=Estructura+guardada');
            exit;
        } catch (Exception $e) {
            $this->renderView('presupuesto/estructuras/form', [
                'titulo'     => 'Estructura Presupuestaria',
                'estructura' => null,
                'error'      => 'Error al guardar: ' . $e->getMessage(),
            ]);
        }
    }

    public function eliminar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->repo->delete((int)$id);
        }
        header('Location: ?route=estruc_presupuestaria/index&success=Estructura+eliminada');
        exit;
    }
}
