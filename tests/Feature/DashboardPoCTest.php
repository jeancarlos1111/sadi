<?php

use App\Controllers\PresupuestoController;
use App\Repositories\PresupuestoGastoRepository;
use App\Repositories\FuenteFinanciamientoRepository;
use App\Repositories\UnidadAdministrativaRepository;
use App\Database\Connection;

test('dashboard method runs without exceptions and uses AMPHP Fibers', function () {
    // Simulate user session to bypass auth check in constructor
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['usuario'] = ['id' => 1, 'nombre' => 'Admin'];

    $pdo = Connection::getInstance();
    $repo = new PresupuestoGastoRepository($pdo);
    $fuenteRepo = new FuenteFinanciamientoRepository($pdo);
    $unidadRepo = new UnidadAdministrativaRepository($pdo);

    $controller = new PresupuestoController($repo, $fuenteRepo, $unidadRepo);

    ob_start();
    $controller->dashboard();
    $output = ob_get_clean();

    expect($output)->toContain('Dashboard Presupuestario (Fibers PoC)');
    expect($output)->toContain('Tiempo de carga asíncrona:');
    expect($output)->toContain('Total Asignado');
    expect($output)->toContain('Top 5 Partidas con Mayor Asignación');
});
