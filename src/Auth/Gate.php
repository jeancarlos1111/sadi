<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * Gate — Puerta de Autorización RBAC.
 *
 * Uso en vistas/controladores:
 *   Gate::allows('presupuesto.gastos.ver')         → bool
 *   Gate::allows('compras.proveedores.crear')       → bool
 *   Gate::authorize('admin.usuarios.ver')           → void (redirige si no tiene permiso)
 *   Gate::isAdmin()                                 → bool
 *
 * Los permisos están en $_SESSION['permisos'] con formato:
 *   ['presupuesto.gastos.ver' => true, 'compras.proveedores.crear' => true, ...]
 */
class Gate
{
    /**
     * Verifica si el usuario activo tiene un permiso específico.
     * Formato: 'modulo.seccion.accion' (ej: 'presupuesto.gastos.ver')
     * También acepta 'modulo.*.accion' para verificar cualquier sección.
     */
    public static function allows(string $permiso): bool
    {
        if (!isset($_SESSION['usuario_id'])) {
            return false;
        }

        $permisos = $_SESSION['permisos'] ?? [];

        // Verificación exacta
        if (isset($permisos[$permiso])) {
            return true;
        }

        // Verificación de wildcard: si tiene 'modulo.*.accion' → acceso a cualquier sección
        $parts = explode('.', $permiso);
        if (count($parts) === 3) {
            [$modulo, $seccion, $accion] = $parts;
            // Si tiene el permiso wildcard del módulo
            if (isset($permisos["{$modulo}.*.{$accion}"])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene AL MENOS UNO de los permisos dados.
     */
    public static function allowsAny(string ...$permisos): bool
    {
        foreach ($permisos as $permiso) {
            if (self::allows($permiso)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene TODOS los permisos dados.
     */
    public static function allowsAll(string ...$permisos): bool
    {
        foreach ($permisos as $permiso) {
            if (!self::allows($permiso)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Exige el permiso dado; si no lo tiene, redirige a 403 y termina.
     */
    public static function authorize(string $permiso): void
    {
        if (!self::allows($permiso)) {
            http_response_code(403);
            $viewsPath = dirname(__DIR__, 2) . '/views/';
            $viewPath = '403';
            $partialsPath = dirname(__DIR__, 2) . '/views/partials/';
            if (file_exists($partialsPath . '403.phtml')) {
                require $partialsPath . '403.phtml';
            } else {
                echo '<h1>403 – Acceso Denegado</h1>';
                echo '<p>No tienes permiso para acceder a este recurso.</p>';
                echo '<a href="?route=home/index">← Volver al inicio</a>';
            }
            exit;
        }
    }

    /**
     * ¿El usuario activo es ADMINISTRADOR?
     * El rol ADMINISTRADOR tiene todos los permisos; esto lo detecta verificando
     * si existe al menos el permiso 'admin.usuarios.ver'.
     */
    public static function isAdmin(): bool
    {
        return self::allows('admin.usuarios.ver');
    }

    /**
     * ¿Hay un usuario autenticado (sesión activa)?
     */
    public static function check(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    /**
     * Nombre del usuario autenticado.
     */
    public static function usuario(): string
    {
        return $_SESSION['usuario_nombre'] ?? 'Invitado';
    }
}
