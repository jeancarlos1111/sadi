<?php

use App\Database\Connection;
use App\Repositories\OrdenCompraRepository;
use Tests\Helpers\DatabaseSeeder;

beforeEach(function () {
    DatabaseSeeder::cleanBudgetTables();
});

test('crear orden de compra verifica disponibilidad presupuestaria (bloqueo FOR UPDATE)', function () {
    $db = Connection::getInstance();

    // Partida con solo 1000.00 disponibles
    $db->exec("INSERT INTO presupuesto_gastos (id_codigo_plan_unico, monto_asignado, monto_comprometido, monto_precomprometido) VALUES (1, 1000.00, 0, 0)");

    $repo = new OrdenCompraRepository($db);

    $cabecera = [
        'fecha_odc'            => '2025-01-01',
        'concepto_odc'         => 'Orden Test',
        'id_proveedor'         => 1,
        'porcentaje_iva_odc'   => 0,
        'monto_base_odc'       => 1500.00,
        'monto_iva_odc'        => 0,
        'monto_total_odc'      => 1500.00,
        // Alias usados en crearConTransaccion
        'fecha'                => '2025-01-01',
        'concepto'             => 'Orden Test',
    ];
    $detalles = [
        // Articulo ID=1 del schema tiene id_codigo_plan_unico=1 (Sueldos)
        ['id_articulo' => 1, 'cantidad_aodc' => 1, 'costo_aodc' => 1500.00, 'aplica_iva' => false]
    ];

    // Debe lanzar excepción porque 1500 > 1000 disponible
    expect(fn() => $repo->crearConTransaccion($cabecera, $detalles))
        ->toThrow(Exception::class, 'Pre-compromiso fallido');
});

test('simula dos usuarios intentando comprometer la misma partida secuencialmente', function () {
    $db = Connection::getInstance();

    // Partida con exactamente 1000.00 disponibles
    $db->exec("INSERT INTO presupuesto_gastos (id_codigo_plan_unico, monto_asignado, monto_comprometido, monto_precomprometido) VALUES (1, 1000.00, 0, 0)");

    $repo = new OrdenCompraRepository($db);

    $cabecera = [
        'fecha_odc' => '2025-01-01', 'concepto_odc' => 'Test', 'id_proveedor' => 1,
        'porcentaje_iva_odc' => 0, 'monto_base_odc' => 600.00,
        'monto_iva_odc' => 0, 'monto_total_odc' => 600.00,
        'fecha' => '2025-01-01', 'concepto' => 'Test',
    ];
    $detalles = [['id_articulo' => 1, 'cantidad_aodc' => 1, 'costo_aodc' => 600.00, 'aplica_iva' => false]];

    // Usuario 1: gasta 600 → queda 400 disponible
    $idOrden1 = $repo->crearConTransaccion($cabecera, $detalles);
    expect($idOrden1)->toBeGreaterThan(0);

    // Para registrar el compromiso, necesitamos contabilizar
    $repo->contabilizar($idOrden1);

    // Usuario 2: intenta gastar 600 más → solo hay 400 → debe fallar
    $cabecera2 = array_merge($cabecera, ['concepto_odc' => 'Test 2', 'concepto' => 'Test 2']);
    expect(fn() => $repo->crearConTransaccion($cabecera2, $detalles))
        ->toThrow(Exception::class, 'Pre-compromiso fallido');
});
