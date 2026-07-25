<?php

declare(strict_types=1);

namespace App\Controllers;

class HomeController extends BaseController
{
    public function __construct()
    {
        // Protected route check
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->renderView('home/index', [
            'titulo' => 'Inicio - SADI',
        ]);
    }
}
