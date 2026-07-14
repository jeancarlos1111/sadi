<?php

use App\Database\Connection;
use App\Repositories\PresupuestoGastoRepository;
use Tests\Helpers\DatabaseSeeder;

beforeEach(function () {
    DatabaseSeeder::seedCatalogs();
    DatabaseSeeder::cleanBudgetTables();
});

test('se puede formular un presupuesto de gasto inicial', function () {
    $db = Connection::getInstance();
    $repo = new PresupuestoGastoRepository($db);

    $db->exec("INSERT INTO presupuesto_gastos (id_presupuesto_gastos, id_estruc_presupuestaria, id_codigo_plan_unico, id_fuente_financiamiento, monto_asignado) VALUES (1, 1, 1, 1, 50000.00)");

    $partidas = $repo->all();
    
    expect($partidas)->toHaveCount(1);
    expect($partidas[0]['entity']->montoAsignado)->toEqual(50000.00);
    expect($partidas[0]['estructura_desc'])->toEqual('Administración Central - Recursos Humanos');
    expect($partidas[0]['partida_desc'])->toEqual('Sueldos Básicos Personal Fijo');
    expect($partidas[0]['fuente_desc'])->toEqual('Recursos Ordinarios');
});
