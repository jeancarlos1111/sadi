<?php

namespace App\Controllers;

use App\Models\TipoOperacionBancaria;
use App\Repositories\TipoOperacionBancariaRepository;
use Exception;

class TiposOperacionesBancariasController extends BaseController
{
    private TipoOperacionBancariaRepository $repo;

    public function __construct(TipoOperacionBancariaRepository $repo)
    {
        $this->repo = $repo;
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        $tipos = $this->repo->all();
        $this->renderView('banco/catalogos/tipos_operacion/index', [
            'titulo' => 'Tipos de Operación Bancaria',
            'tipos'  => $tipos,
            'success' => $_GET['success'] ?? null,
            'error'   => $_GET['error'] ?? null,
        ]);
    }

    public function form(): void
    {
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
