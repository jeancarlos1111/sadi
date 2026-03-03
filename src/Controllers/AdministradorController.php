<?php

namespace App\Controllers;

use App\Repositories\UsuarioRepository;
use PDOException;

class AdministradorController extends BaseController
{
    private UsuarioRepository $repo;

    public function __construct(UsuarioRepository $repo)
    {
        $this->repo = $repo;

        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function usuarios(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $usuarios = $this->repo->all($search);
        } catch (PDOException $e) {
            $usuarios = [];
            $error = "Error al obtener la lista de usuarios: " . $e->getMessage();
        }

        $this->renderView('administrador/usuarios/index', [
            'titulo' => 'Gestión de Usuarios',
            'usuarios' => $usuarios,
            'search' => $search,
            'error' => $error ?? null,
        ]);
    }

    public function configuracion(): void
    {
        $this->renderView('administrador/configuracion/index', [
            'titulo' => 'Configuración Global',
            'error' => 'La vista para modificar los parámetros globales del sistema y realizar el cierre/apertura anual está en desarrollo.',
        ]);
    }
}
