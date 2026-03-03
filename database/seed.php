<?php

require_once __DIR__ . '/../src/Database/Connection.php';

use App\Database\Connection;

echo "Inicializando Base de Datos SQLite para Desarrollo...\n";

try {
    $db = Connection::getInstance();
    
    $schemaPath = __DIR__ . '/schema.sql';
    if (!file_exists($schemaPath)) {
        die("Error: No se encontró schema.sql en $schemaPath\n");
    }
    
    $sql = file_get_contents($schemaPath);
    
    if ($db->exec($sql) !== false) {
        echo "Esquema y datos de prueba importados correctamente.\n";
    } else {
        echo "Hubo un problema importando el esquema.\n";
        print_r($db->errorInfo());
    }
    
    // Test the data existence
    $stmt = $db->query("SELECT COUNT(*) FROM proveedor");
    echo "Proveedores registrados: " . $stmt->fetchColumn() . "\n";
    
} catch (\Exception $e) {
    echo "Error inicializando la BD: " . $e->getMessage() . "\n";
}
