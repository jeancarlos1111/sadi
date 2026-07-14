<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class BaseController
{
    protected function toDTO($data)
    {
        if (is_object($data)) {
            $class = get_class($data);
            if (str_starts_with($class, 'App\\Models\\')) {
                $dtoClass = str_replace('App\\Models\\', 'App\\DTOs\\', $class) . 'DTO';
                if (class_exists($dtoClass) && method_exists($dtoClass, 'fromModel')) {
                    return $dtoClass::fromModel($data);
                }
            }

            return $data;
        } elseif (is_array($data)) {
            $mapped = [];
            foreach ($data as $k => $v) {
                $mapped[$k] = $this->toDTO($v);
            }

            return $mapped;
        }

        return $data;
    }

    protected function renderView(string $viewPath, array $data = []): void
    {
        $data = $this->toDTO($data);
        $data['viewPath'] = $viewPath;
        $data['route'] = $_GET['route'] ?? 'home/index';
        $data['partialsPath'] = dirname(__DIR__, 2) . '/views/partials/';
        extract($data);
        $viewsPath = dirname(__DIR__, 2) . '/views/';
        require_once $viewsPath . 'layouts/main.phtml';
    }
}
