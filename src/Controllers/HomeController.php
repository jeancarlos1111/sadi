<?php

namespace App\Controllers;

class HomeController extends BaseController
{
    public function __construct()
    {
        // Protected route check
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        $this->renderView('home/index', [
            'titulo' => 'Inicio - SADI',
        ]);
    }
}
