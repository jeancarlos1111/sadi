<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

abstract class BaseController
{
    /**
     * Verifica que el usuario tenga sesión activa.
     * Si no, redirige al login.
     */
    protected function requireAuth(): void
    {
        if (!Gate::check()) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    /**
     * Exige un permiso específico (formato: 'modulo.seccion.accion').
     * Si no lo tiene, Gate::authorize() retorna 403.
     */
    protected function requirePermiso(string $permiso): void
    {
        $this->requireAuth();
        Gate::authorize($permiso);
    }

    protected function toDTO(mixed $data): mixed
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
        $data            = $this->toDTO($data);
        $data['viewPath'] = $viewPath;
        $data['route']   = $_GET['route'] ?? 'home/index';
        $data['partialsPath'] = dirname(__DIR__, 2) . '/views/partials/';
        extract($data);
        $viewsPath = dirname(__DIR__, 2) . '/views/';
        
        if (!empty($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            require $viewsPath . $viewPath . '.phtml';
        } else {
            require $viewsPath . 'layouts/main.phtml';
        }
    }

    /**
     * Registra un evento en la pista de auditoría.
     */
    protected function audit(string $tabla, string $accion, int $idRegistro, ?array $antes = null, ?array $despues = null): void
    {
        \App\Core\Auditor::log($tabla, $accion, $idRegistro, $antes, $despues);
    }
}
