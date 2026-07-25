<?php

declare(strict_types=1);

if (!function_exists('render_component')) {
    /**
     * Renderiza un componente (vista parcial) pasándole un array de "props".
     *
     * @param string $name Nombre del componente (sin .phtml), ej: 'table_actions'
     * @param array $props Variables a inyectar en la vista del componente
     * @return string HTML renderizado
     */
    function render_component(string $name, array $props = []): string
    {
        $path = dirname(__DIR__, 2) . '/views/components/' . $name . '.phtml';
        
        if (!file_exists($path)) {
            return "<!-- Component '{$name}' not found -->";
        }
        
        extract($props);
        
        ob_start();
        require $path;
        return ob_get_clean();
    }
}
