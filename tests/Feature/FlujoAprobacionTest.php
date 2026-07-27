<?php

declare(strict_types=1);

use App\Repositories\OrdenCompraRepository;
use App\Services\FlujoAprobacionService;

use App\Database\Connection;

test('Una Orden de Compra no puede contabilizarse si está en ELABORACION o REVISION', function () {
    $db = Connection::getInstance();
    
    // Configuración base (Mock de OC)
    $db->exec("INSERT INTO proveedor (compania_proveedor) VALUES ('Prov Test')");
    $idProv = (int)$db->lastInsertId();

    $db->exec("INSERT INTO orden_de_compra (id_proveedor, estado_aprobacion, contabilizada) VALUES ($idProv, 'ELABORACION', false)");
    $idOc = (int)$db->lastInsertId();

    // Agregar un artículo y partida para la prueba de contabilización
    $db->exec("INSERT INTO plan_unico_cuentas (id_codigo_plan_unico, codigo_plan_unico) VALUES (999, '1.2.3.4') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO estruc_presupuestaria (id_estruc_presupuestaria, descripcion_ep) VALUES (1, 'EP1') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO fuente_financiamiento (id_fuente_financiamiento, denominacion) VALUES (1, 'FF1') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO presupuesto_gastos (id_presupuesto_gastos, id_codigo_plan_unico, id_estruc_presupuestaria, id_fuente_financiamiento, monto_asignado) VALUES (999, 999, 1, 1, 1000)");
    $db->exec("INSERT INTO articulo (id_articulo, id_codigo_plan_unico, denominacion_a) VALUES (999, 999, 'Articulo Test') ON CONFLICT DO NOTHING");
    $idArt = 999;
    $db->exec("INSERT INTO articulo_orden_de_compra (id_orden_de_compra, id_articulo, cantidad_aodc, costo_aodc) VALUES ($idOc, $idArt, 1, 10)");

    $repo = new OrdenCompraRepository($db);

    // Intento 1: ELABORACION -> Falla
    $exceptionThrown = false;
    try {
        $repo->contabilizar($idOc);
    } catch (\Exception $e) {
        $exceptionThrown = true;
        expect($e->getMessage())->toContain('Bloqueo Financiero');
    }
    expect($exceptionThrown)->toBeTrue();

    // Pasar a REVISION usando servicio
    $flujo = new FlujoAprobacionService();
    $flujo->cambiarEstado('ORDEN_COMPRA', $idOc, 'REVISION', 'Test', 1);

    // Intento 2: REVISION -> Falla
    $exceptionThrown = false;
    try {
        $repo->contabilizar($idOc);
    } catch (\Exception $e) {
        $exceptionThrown = true;
        expect($e->getMessage())->toContain('Bloqueo Financiero');
    }
    expect($exceptionThrown)->toBeTrue();

    // Pasar a APROBADO
    $flujo->cambiarEstado('ORDEN_COMPRA', $idOc, 'PRE-APROBADO', 'Test', 1);
    $flujo->cambiarEstado('ORDEN_COMPRA', $idOc, 'APROBADO', 'Test', 1);

    // Intento 3: APROBADO -> Éxito
    $resultado = $repo->contabilizar($idOc);
    expect($resultado)->toBeTrue();
});
