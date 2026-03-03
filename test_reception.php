<?php
// Basic Autoloader for SADI
error_reporting(E_ALL);
ini_set('display_errors', '1');

spl_autoload_register(function (string $class) {
    $baseDir = __DIR__ . '/src/';
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\RecepcionAlmacen;
use App\Database\Connection;

// Mock session/auth if needed (Connection.php doesn't use it but just in case)
session_start();
$_SESSION['usuario'] = 'DiagnosticAgent';

try {
    echo "Iniciando prueba de recepción manual para OC #1...\n";
    
    $idOrden = 1;
    $articulos = [
        ['id_articulo' => 1, 'cantidad' => 5]
    ];

    $res = RecepcionAlmacen::recibirArticulos($idOrden, $articulos);
    
    if ($res) {
        echo "¡Éxito! La función devolvió TRUE.\n";
    } else {
        echo "La función devolvió FALSE.\n";
    }

} catch (Exception $e) {
    echo "ERROR CAPTURADO: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

// Verificar contenido de documento
$db = Connection::getInstance();
$stmt = $db->query("SELECT * FROM documento");
$docs = $stmt->fetchAll();
echo "Documentos en modulo_cxp.documento: " . count($docs) . "\n";
foreach($docs as $d) {
    print_r($d);
}
