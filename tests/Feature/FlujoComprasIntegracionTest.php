<?php

use App\Database\Connection;
use App\Services\FlujoAprobacionService;
use App\Repositories\OrdenCompraRepository;

test('Flujo Completo de Compras: OC -> Pre-compromiso -> Aprobacion -> Compromiso', function () {
    $db = Connection::getInstance();

    // Limpiar tablas para evitar conflictos
    $db->exec("DELETE FROM articulo_orden_de_compra");
    $db->exec("DELETE FROM orden_de_compra");
    $db->exec("DELETE FROM auditoria_log");

    // Preparar Presupuesto Inicial (Partida 401.01.01.00)
    // Asegurarnos de que existe la partida 1 y presupuesto_gastos 9999
    $db->exec("INSERT INTO plan_unico_cuentas (id_codigo_plan_unico, codigo_plan_unico, denominacion) VALUES (1, '4.01.01.01.00', 'Partida Test') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO estruc_presupuestaria (id_estruc_presupuestaria, descripcion_ep) VALUES (1, '01') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO fuente_financiamiento (id_fuente_financiamiento, denominacion) VALUES (1, 'Fuente Test') ON CONFLICT DO NOTHING");
    
    $db->exec("
        INSERT INTO presupuesto_gastos (id_presupuesto_gastos, id_codigo_plan_unico, id_estruc_presupuestaria, id_fuente_financiamiento, monto_asignado, monto_precomprometido, monto_comprometido)
        VALUES (9999, 1, 1, 1, 10000.00, 0, 0)
        ON CONFLICT (id_presupuesto_gastos) DO UPDATE SET 
            monto_asignado = 10000.00, 
            monto_precomprometido = 0, 
            monto_comprometido = 0
    ");

    // Crear Proveedor
    $db->exec("INSERT INTO proveedor (id_proveedor, rif_proveedor, compania_proveedor) VALUES (9999, 'J-12345678-9', 'Proveedor Test C.A.') ON CONFLICT DO NOTHING");

    // Crear Artículo
    $db->exec("INSERT INTO articulo (id_articulo, id_codigo_plan_unico, denominacion_a) VALUES (9999, 1, 'Articulo Test') ON CONFLICT DO NOTHING");

    // 1. Crear Orden de Compra (ELABORACION)
    $db->exec("
        INSERT INTO orden_de_compra (fecha_odc, concepto_odc, id_proveedor, porcentaje_iva_odc, estado_aprobacion)
        VALUES ('2023-01-01', 'Compra de prueba', 9999, 16.0, 'ELABORACION')
    ");
    $idOc = (int)$db->lastInsertId();

    $db->exec("
        INSERT INTO articulo_orden_de_compra (id_orden_de_compra, id_articulo, cantidad_aodc, costo_aodc, aplica_iva)
        VALUES ($idOc, 9999, 10, 100.00, true)
    "); // Subtotal: 1000.00, IVA 16%: 160.00 => Total: 1160.00

    // Verificar que en ELABORACION el presupuesto no se afecta
    $ppto = $db->query("SELECT monto_precomprometido, monto_comprometido FROM presupuesto_gastos WHERE id_codigo_plan_unico = 1")->fetch();
    expect((float)$ppto['monto_precomprometido'])->toBe(0.0, 'ELABORACION no debe pre-comprometer.');
    expect((float)$ppto['monto_comprometido'])->toBe(0.0, 'ELABORACION no debe comprometer.');

    // 2. Avanzar por el Flujo
    $flujo = new FlujoAprobacionService();
    
    // De ELABORACION -> REVISION
    $flujo->cambiarEstado('ORDEN_COMPRA', $idOc, 'REVISION', 'Test', 1);
    
    // Validar estado actual
    $estado = $db->query("SELECT estado_aprobacion FROM orden_de_compra WHERE id_orden_de_compra = $idOc")->fetchColumn();
    expect($estado)->toBe('REVISION');

    // De REVISION -> PRE-APROBADO
    $flujo->cambiarEstado('ORDEN_COMPRA', $idOc, 'PRE-APROBADO', 'Test', 1);

    // Validar Pista de Auditoría (o en este caso historial_aprobacion)
    $logs = $db->query("SELECT * FROM historial_aprobacion WHERE tipo_documento = 'ORDEN_COMPRA' AND id_documento = $idOc")->fetchAll();
    expect(count($logs))->toBeGreaterThanOrEqual(2, 'Debe haber logs de auditoría para los cambios de estado');

    // De PRE-APROBADO -> APROBADO (Genera el Compromiso Presupuestario)
    $flujo->cambiarEstado('ORDEN_COMPRA', $idOc, 'APROBADO', 'Test', 1);

    $estadoFinal = $db->query("SELECT estado_aprobacion FROM orden_de_compra WHERE id_orden_de_compra = $idOc")->fetchColumn();
    expect($estadoFinal)->toBe('APROBADO');

    // Contabilizar (Genera el Compromiso Presupuestario)
    $repo = new OrdenCompraRepository($db);
    $repo->contabilizar($idOc);

    // Verificar que al contabilizarse, se generó el Compromiso Presupuestario
    $pptoFinal = $db->query("SELECT monto_precomprometido, monto_comprometido FROM presupuesto_gastos WHERE id_codigo_plan_unico = 1")->fetch();
    expect((float)$pptoFinal['monto_comprometido'])->toBeGreaterThan(0.0, 'El compromiso debe ser mayor a 0 tras contabilizar la orden.');
});
