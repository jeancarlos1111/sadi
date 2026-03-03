<?php

namespace App\Controllers;

class AuthController extends BaseController
{
    public function login(): void
    {
        // Si ya hay sesión, redirigir al home
        if (isset($_SESSION['usuario'])) {
            header('Location: ?route=home/index');
            exit;
        }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = $_POST['usuario'] ?? '';
            $password = $_POST['password'] ?? '';

            // En un caso real, validaríamos contra la BD (por ej. tabla `usuario`).
            // Para propósitos de este piloto de SADI, simulamos un login básico si el usuario no está vacío.
            if ($usuario === 'admin' && $password === '1234') { // Hardcoded for demo
                $_SESSION['usuario'] = $usuario;
                $_SESSION['BDnombre_base_datos'] = 'sigafs'; // Needed by Connection mapping
                header('Location: ?route=home/index');
                exit;
            } else {
                $error = "Usuario o contraseña incorrectos. (Pista: admin / 1234)";
            }
        }

        // Usaremos una vista especial sin el layout principal (o un layout limpio)
        require_once __DIR__ . '/../../views/auth/login.phtml';
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: ?route=auth/login');
        exit;
    }
}
