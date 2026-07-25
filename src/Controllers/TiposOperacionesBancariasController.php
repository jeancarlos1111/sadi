<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

use App\Models\TipoOperacionBancaria;
use App\Repositories\TipoOperacionBancariaRepository;
use Exception;

class TiposOperacionesBancariasController extends BaseController
{
    private TipoOperacionBancariaRepository $repo;

    public function __construct(TipoOperacionBancariaRepository $repo)
    {
        $this->repo = $repo;
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        Gate::authorize('tesoreria.operaciones_bancarias.ver');
        $page = (int)($_GET['page'] ?? 1);
        $paginator = $this->repo->paginate('', $page, 15);
        $tipos = $paginator['data'];
        $this->renderView('banco/catalogos/tipos_operacion/index', [
            'titulo' => 'Tipos de Operación Bancaria',
            'tipos'  => $tipos,
            'success' => $_GET['success'] ?? null,
            'error'   => $_GET['error'] ?? null,
                    'paginator' => $paginator,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        Gate::authorize($id ? 'tesoreria.operaciones_bancarias.editar' : 'tesoreria.operaciones_bancarias.crear');
        $id = (int)($_GET['id'] ?? 0);
        $tipo = $id ? $this->repo->find($id) : null;

        $this->renderView('banco/catalogos/tipos_operacion/form', [
            'titulo' => $id ? 'Editar Tipo de Operación' : 'Nuevo Tipo de Operación',
            'tipo'   => $tipo,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=tipos_operaciones_bancarias/index');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $acronimo = trim($_POST['acronimo'] ?? '');

        if (empty($nombre) || empty($acronimo)) {
            header("Location: ?route=tipos_operaciones_bancarias/form&id=$id&error=Todos los campos son obligatorios");
            exit;
        }

        try {
            $item = new TipoOperacionBancaria($id, $nombre, $acronimo);
            if ($this->repo->save($item)) {
                header('Location: ?route=tipos_operaciones_bancarias/index&success=Tipo de operación guardado correctamente');
            } else {
                throw new Exception("No se pudo guardar");
            }
        } catch (Exception $e) {
            header("Location: ?route=tipos_operaciones_bancarias/form&id=$id&error=" . urlencode($e->getMessage()));
        }
    }

    public function eliminar(): void
    {
        Gate::authorize('tesoreria.operaciones_bancarias.eliminar');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=tipos_operaciones_bancarias/index');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($this->repo->delete($id)) {
            header('Location: ?route=tipos_operaciones_bancarias/index&success=Registro eliminado');
        } else {
            header('Location: ?route=tipos_operaciones_bancarias/index&error=Error al eliminar');
        }
    }
}
