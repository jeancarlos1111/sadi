<?php

declare(strict_types=1);

session_start();

// Basic Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Cargar Autoloader de Composer (PSR-4 estricto)
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Container;

// Very Basic Router
$route = $_GET['route'] ?? 'home/index';
$parts = explode('/', trim($route, '/'));

$controllerName = 'Home';
if (!empty($parts[0])) {
    $rawModule = strtolower($parts[0]);
    // Aliases for common abbreviations
    if ($rawModule === 'cxp') $parts[0] = 'cuentas_por_pagar';
    if ($rawModule === 'ppto') $parts[0] = 'presupuesto';
    
    $controllerName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $parts[0])));
}
$actionRaw = $parts[1] ?? 'index';
$action = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $actionRaw))));

$controllerClass = "App\\Controllers\\{$controllerName}Controller";

if (class_exists($controllerClass) && method_exists($controllerClass, $action)) {
    // Inicializar el Contenedor de Inyección de Dependencias
    $container = new Container();
    
    // Registrar el propio contenedor (opcional pero útil)
    $container->singleton(Container::class, $container);

    try {
        // En vez de: $controller = new $controllerClass();
        // Le pedimos al contenedor que lo resuelva (Auto-wiring)
        $controller = $container->get($controllerClass);
        $controller->$action();
    } catch (\Exception $e) {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1>";
        echo "<p>Error de Inyección de Dependencias: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
    echo "<p>Ruta no encontrada: " . htmlspecialchars($route) . "</p>";
}
