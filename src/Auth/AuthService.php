<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\Usuario;
use App\Repositories\UsuarioRepository;

/**
 * Servicio de Autenticación.
 * Gestiona login, logout y carga de permisos en sesión.
 */
class AuthService
{
    public function __construct(
        private readonly UsuarioRepository $usuarioRepo
    ) {
    }

    /**
     * Autentica un usuario con sus credenciales.
     * Si es válido, guarda el usuario y sus permisos en sesión.
     *
     * @throws \InvalidArgumentException si la contraseña tiene menos de 8 caracteres.
     */
    public function login(string $usuario, string $password): bool
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
        }

        $usuarioEntity = $this->usuarioRepo->findByCredentials($usuario, $password);

        if (!$usuarioEntity) {
            return false;
        }

        $this->guardarSesion($usuarioEntity);

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public function getUsuarioActual(): ?Usuario
    {
        if (!isset($_SESSION['usuario_id'])) {
            return null;
        }

        return $this->usuarioRepo->find((int)$_SESSION['usuario_id']);
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    /**
     * Recarga los permisos del usuario actual desde la BD y los guarda en sesión.
     */
    public function refrescarPermisos(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            return;
        }

        $permisos = $this->usuarioRepo->getPermisos((int)$_SESSION['usuario_id']);
        $_SESSION['permisos'] = $permisos;
    }

    // -------------------------------------------------------------------------

    private function guardarSesion(Usuario $usuario): void
    {
        session_regenerate_id(true);

        $_SESSION['usuario_id']     = $usuario->id;
        $_SESSION['usuario_nombre'] = $usuario->usuario;

        // Carga inmediata de permisos para este usuario
        $permisos = $this->usuarioRepo->getPermisos($usuario->id);
        $_SESSION['permisos'] = $permisos;
    }
}
