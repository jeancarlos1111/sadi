<?php

namespace App\Controllers;

abstract class BaseController
{
    protected function renderView(string $viewPath, array $data = []): void
    {
        extract($data);
        $viewsPath = dirname(__DIR__, 2) . '/views/';
        require_once $viewsPath . 'layouts/main.phtml';
    }
}
