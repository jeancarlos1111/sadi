<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

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
        Gate::authorize('presupuesto.estructuras.ver');
        $search = $_GET['search'] ?? '';
        $estructuras = [];

        try {
            $page = (int)($_GET['page'] ?? 1);
            $paginator = $this->repo->paginate('', $page, 15);
            $estructuras = $paginator['data'];
        } catch (PDOException $e) {
            error_log("Error fetching estructuras: " . $e->getMessage());
        }

        $this->renderView('presupuesto/estructuras/index', [
            'titulo'      => 'Estructuras Presupuestarias',
            'estructuras' => $estructuras,
            'search'      => $search,
                    'paginator' => $paginator,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        Gate::authorize($id ? 'presupuesto.estructuras.editar' : 'presupuesto.estructuras.crear');
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
            $datosAntes = null;
            if ($id) {
                $modeloAnterior = $this->repo->find($id);
                $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
            }

            $ep = new EstructuraPresupuestaria($id ?: 0, $descripcion);
            $nuevoId = $this->repo->save($ep);

            $modeloDespues = $this->repo->find($nuevoId);
            $this->audit('estructura_presupuestaria', $id ? 'EDITAR' : 'CREAR', $nuevoId, $datosAntes, $modeloDespues ? $modeloDespues->toArray() : null);

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
        Gate::authorize('presupuesto.estructuras.eliminar');
        $id = $_POST['id'] ?? null;
        if ($id) {
            $id = (int)$id;
            $modeloAnterior = $this->repo->find($id);
            $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
            $this->repo->delete($id);
            $this->audit('estructura_presupuestaria', 'ELIMINAR', $id, $datosAntes, null);
        }
        header('Location: ?route=estruc_presupuestaria/index&success=Estructura+eliminada');
        exit;
    }
}
