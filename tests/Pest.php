<?php

use App\Database\Connection;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses()->beforeEach(function () {
    // Cargar configuración de testing desde .env.testing
    $baseDir = dirname(__DIR__);
    $envFile = $baseDir . '/.env.testing';
    $config  = parse_ini_file($envFile);

    // Crear conexión PostgreSQL de pruebas
    $dsn = "pgsql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_DATABASE']}";
    $pdo = new PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Inyectar la conexión de testing
    Connection::setInstance($pdo);

    // Limpiar todas las tablas de negocio antes de cada test para aislamiento
    $pdo->exec("
        TRUNCATE TABLE
            articulo_orden_de_compra,
            servicio_orden_de_servicio,
            orden_de_compra,
            orden_de_servicio,
            articulo_requisicion_bienes,
            servicio_requisicion_servicios,
            requisicion_bienes,
            requisicion_servicios,
            pac,
            movimiento_presupuestario,
            presupuesto_gastos,
            presupuesto_ingresos,
            periodo_presupuestario
        RESTART IDENTITY CASCADE
    ");
})->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

require_once __DIR__ . '/Helpers/DatabaseSeeder.php';
