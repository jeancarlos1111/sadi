<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthService;

class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    public function login(): void
    {
        if ($this->authService->isLoggedIn()) {
            header('Location: ?route=home/index');
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario  = trim($_POST['usuario'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($usuario) || empty($password)) {
                $error = 'Por favor ingrese usuario y contraseña.';
            } elseif (strlen($password) < 8) {
                $error = 'La contraseña debe tener al menos 8 caracteres.';
            } else {
                try {
                    $autenticado = $this->authService->login($usuario, $password);

                    if ($autenticado) {
                        header('Location: ?route=home/index');
                        exit;
                    } else {
                        $error = 'Usuario o contraseña incorrectos.';
                    }
                } catch (\InvalidArgumentException $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $this->renderView('auth/login', [
            'titulo' => 'Iniciar Sesión – SADI',
            'error'  => $error,
        ]);
    }

    public function logout(): void
    {
        $this->authService->logout();
        header('Location: ?route=auth/login');
        exit;
    }
}
