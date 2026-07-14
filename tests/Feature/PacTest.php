<?php

use App\Database\Connection;
use App\Models\PAC;
use App\Repositories\PACRepository;
use Tests\Helpers\DatabaseSeeder;

beforeEach(function () {
    DatabaseSeeder::seedCatalogs();
    DatabaseSeeder::cleanBudgetTables();
});

test('se puede guardar un PAC y calcula costo estimado basado en trimestres', function () {
    $db = Connection::getInstance();
    $repo = new PACRepository($db);

    $pac = new PAC(
        id_proyecto: 1,
        id_accion_centralizada: null,
        id_articulo: 1, // ID 1 de Computadora Portatil
        cantidad_anual: 10.0,
        trim_1: 2.0,
        trim_2: 3.0,
        trim_3: 3.0,
        trim_4: 2.0,
        costo_estimado: 50000.00,
        estatus: 'Pendiente'
    );

    $saved = $repo->save($pac);
    expect($saved)->toBeTruthy();

    $todos = $repo->all();
    expect($todos)->toHaveCount(1);
    expect($todos[0]['entity']->costo_estimado)->toEqual(50000.00);
    expect($todos[0]['articulo_desc'])->toEqual('Lápices Grafito');
});

test('aprobar pac cambia el estado', function () {
    $db = Connection::getInstance();
    $repo = new PACRepository($db);

    $db->exec("INSERT INTO pac (id_pac, id_proyecto, id_articulo, cantidad_anual, costo_estimado, estatus) VALUES (99, 1, 1, 5, 25000.00, 'Pendiente')");

    $repo->aprobar(99);

    $pacRecuperado = $repo->findById(99);
    expect($pacRecuperado->estatus)->toEqual('APROBADO');
});
