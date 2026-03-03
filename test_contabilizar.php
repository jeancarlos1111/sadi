<?php
// Script de prueba simple
error_reporting(E_ALL);
ini_set('display_errors', '1');

spl_autoload_register(function (string $class) {
    if (strncmp('App\\', $class, 4) !== 0) return;
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\OrdenServicio;

try {
    echo "Intentando contabilizar OS #1...\n";
    var_dump(OrdenServicio::contabilizar(1));
    echo "Exito!\n";
} catch (Exception $e) {
    echo "Error atrapado: " . $e->getMessage() . "\n";
}
