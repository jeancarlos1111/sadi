<?php

namespace App\Controllers;

use App\Models\Banco;
use App\Repositories\BancoRepository;
use Exception;

class BancosController extends BaseController
{
    private BancoRepository $repo;

    public function __construct(BancoRepository $repo)
    {
        $this->repo = $repo;
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        $bancos = $this->repo->all();
        $this->renderView('banco/catalogos/bancos/index', [
            'titulo' => 'Maestro de Bancos',
            'bancos' => $bancos,
            'success' => $_GET['success'] ?? null,
            'error'   => $_GET['error'] ?? null,
        ]);
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $banco = $id ? $this->repo->find($id) : null;

        $this->renderView('banco/catalogos/bancos/form', [
            'titulo' => $id ? 'Editar Banco' : 'Nuevo Banco',
            'banco'  => $banco,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=bancos/index');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre_banco'] ?? '');

        if (empty($nombre)) {
            header("Location: ?route=bancos/form&id=$id&error=El nombre es obligatorio");
            exit;
        }

        try {
            $banco = new Banco($id, $nombre);
            if ($this->repo->save($banco)) {
                header('Location: ?route=bancos/index&success=Banco guardado correctamente');
            } else {
                throw new Exception("No se pudo guardar el banco");
            }
        } catch (Exception $e) {
            header("Location: ?route=bancos/form&id=$id&error=" . urlencode($e->getMessage()));
        }
    }

    public function eliminar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=bancos/index');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($this->repo->delete($id)) {
            header('Location: ?route=bancos/index&success=Banco eliminado');
        } else {
            header('Location: ?route=bancos/index&error=Error al eliminar el banco');
        }
    }
}
