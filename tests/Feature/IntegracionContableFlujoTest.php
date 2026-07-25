<?php

use App\Database\Connection;
use App\Models\CuentaBancaria;
use App\Repositories\AsientoContableRepository;
use App\Repositories\CuentaBancariaRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\SolicitudPagoRepository;
use App\Services\IntegracionContableService;

test('Flujo completo de integración contable: OC -> Causado -> Pago', function () {
    $db = Connection::getInstance();

    // Limpiar tablas para evitar conflictos con tests anteriores
    $db->exec("DELETE FROM movimiento_contable");
    $db->exec("DELETE FROM comprobante_diario");
    $db->exec("DELETE FROM articulo_orden_de_compra");
    $db->exec("DELETE FROM orden_de_compra");
    $db->exec("DELETE FROM documento");
    $db->exec("DELETE FROM solicitud_pago");

    // 1. Preparar datos base (Proveedor, Cuenta Bancaria, Partida, etc.)
    // Insertar Tipo de Operacion Bancaria (ID 1)
    $db->exec("INSERT INTO tipo_operacion_bancaria (id_tipo_operacion_bancaria, nombre_tipo_operacion_bancaria) VALUES (1, 'Transferencia') ON CONFLICT DO NOTHING");
    
    // Crear Banco y Cuenta Bancaria
    $db->exec("INSERT INTO banco (id_banco, nombre_banco) VALUES (9999, 'Banco Test') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO cta_bancaria (id_cta_bancaria, id_banco, numero_cta_bancaria, id_cuenta_contable) VALUES (9999, 9999, '0102-0000-0000-1111', 1) ON CONFLICT DO NOTHING");
    $idCtaBancaria = 9999;

    // Crear Proveedor
    $db->exec("INSERT INTO proveedor (id_proveedor, rif_proveedor, compania_proveedor) VALUES (9999, 'J-12345678-9', 'Proveedor Test C.A.') ON CONFLICT DO NOTHING");
    
    // Preparar Dependencias Presupuesto y Contabilidad
    $db->exec("INSERT INTO cuenta_contable (id_cuenta_contable, codigo_cuenta, denominacion_cuenta) VALUES (1, '1.1.1.01.01', 'Banco Test Cuenta') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO cuenta_contable (id_cuenta_contable, codigo_cuenta, denominacion_cuenta) VALUES (2, '2.1.1.01.01', 'Cuentas por Pagar Proveedores') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO cuenta_contable (id_cuenta_contable, codigo_cuenta, denominacion_cuenta) VALUES (3, '5.1.1.01.01', 'Gasto Test') ON CONFLICT DO NOTHING");

    $db->exec("INSERT INTO tipo_documento (id_tipo_documento, denominacion_tipo_documento, afecta_presupuesto_tipo_documento) VALUES (1, 'Factura', true) ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO plan_unico_cuentas (id_codigo_plan_unico, codigo_plan_unico, denominacion) VALUES (1, '4.01.01.01.00', 'Partida Test') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO estruc_presupuestaria (id_estruc_presupuestaria, descripcion_ep) VALUES (1, '01') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO fuente_financiamiento (id_fuente_financiamiento, denominacion) VALUES (1, 'Fuente Test') ON CONFLICT DO NOTHING");

    $db->exec("INSERT INTO vinculacion_puc_contable (id_vinculacion, id_codigo_plan_unico, id_cuenta_contable, tipo_operacion) VALUES (1, 1, 3, 'CAUSADO') ON CONFLICT DO NOTHING");

    // Preparar Presupuesto (Partida 401.01.01.00 - Asumimos ID 1)
    $db->exec("
        INSERT INTO presupuesto_gastos (id_presupuesto_gastos, id_codigo_plan_unico, id_estruc_presupuestaria, id_fuente_financiamiento, monto_asignado, monto_causado, monto_pagado)
        VALUES (9999, 1, 1, 1, 10000.00, 0, 0)
        ON CONFLICT DO NOTHING
    ");

    // Crear Orden de Compra
    $db->exec("
        INSERT INTO orden_de_compra (fecha_odc, concepto_odc, id_proveedor, porcentaje_iva_odc)
        VALUES ('2023-01-01', 'Compra de prueba', 9999, 16.0)
    ");
    $idOc = (int)$db->lastInsertId();

    // Crear Artículo
    $db->exec("INSERT INTO articulo (id_articulo, id_codigo_plan_unico, denominacion_a) VALUES (9999, 1, 'Articulo Test') ON CONFLICT DO NOTHING");
    
    // Detalle OC
    $db->exec("
        INSERT INTO articulo_orden_de_compra (id_orden_de_compra, id_articulo, cantidad_aodc, costo_aodc, aplica_iva)
        VALUES ($idOc, 9999, 10, 100.00, true)
    "); // Subtotal: 1000.00, IVA 16%: 160.00 => Total: 1160.00

    // 2. Ejecutar CAUSADO (Crear Documento/Factura)
    $docRepo = new DocumentoRepository();
    $docRepo->registrarFacturaYRetenciones([
        'id_proveedor' => 9999,
        'fecha_emision_d' => '2023-01-02',
        'nro_documento_d' => 'F-001',
        'nro_control_d' => 'C-001',
        'id_orden_de_compra' => $idOc,
        'observacion_d' => 'Factura por compra de prueba',
        'monto_base_d' => 1000.00,
        'monto_impuesto_d' => 160.00,
        'monto_total_d' => 1160.00,
        'detalles' => [], // Sin retenciones para la prueba simple
    ]);

    // Verificar que se creó el asiento del Causado
    $asientosCausado = $db->query("SELECT * FROM comprobante_diario WHERE concepto LIKE '%Causado Factura N° F-001%'")->fetchAll();
    expect($asientosCausado)->toHaveCount(1, 'No se generó el asiento contable del Causado.');
    $idComprobanteCausado = $asientosCausado[0]['id_comprobante_diario'];

    $detallesCausado = $db->query("SELECT * FROM movimiento_contable WHERE id_comprobante_diario = $idComprobanteCausado")->fetchAll();
    expect(count($detallesCausado))->toBeGreaterThanOrEqual(2, 'El asiento de causado debe tener al menos un Debe y un Haber.');
    
    // Verificar que el presupuesto se afectó
    $ppto = $db->query("SELECT monto_causado FROM presupuesto_gastos WHERE id_codigo_plan_unico = 1")->fetch();
    expect((float)$ppto['monto_causado'])->toBe(1160.0, 'El monto causado en presupuesto es incorrecto.');

    // 3. Crear Solicitud de Pago
    $db->exec("
        INSERT INTO solicitud_pago (fecha_solicitud_pago, concepto_solicitud_pago, monto_pagar_solicitud_pago, id_documento)
        VALUES ('2023-01-03', 'Pago de factura F-001', 1160.00, (SELECT MAX(id_documento) FROM documento))
    ");
    $idSolicitud = (int)$db->lastInsertId();

    // 4. Ejecutar PAGO
    $solRepo = new SolicitudPagoRepository();
    $pagoExitoso = $solRepo->registrarPago($idSolicitud, [
        'id_cta_bancaria' => $idCtaBancaria,
        'id_tipo_operacion_bancaria' => 1, // 1 = Transferencia (asumiendo)
        'fecha_pago' => '2023-01-03',
        'referencia' => 'REF-9999'
    ]);

    expect($pagoExitoso)->toBeTrue();

    // Verificar que el presupuesto se afectó (Pagado)
    $pptoPago = $db->query("SELECT monto_pagado FROM presupuesto_gastos WHERE id_codigo_plan_unico = 1")->fetch();
    expect((float)$pptoPago['monto_pagado'])->toBe(1160.0, 'El monto pagado en presupuesto es incorrecto.');

    // Verificar asiento del Pago
    $asientosPago = $db->query("SELECT * FROM comprobante_diario WHERE concepto LIKE '%Pago Solicitud #{$idSolicitud}%'")->fetchAll();
    expect($asientosPago)->toHaveCount(1, 'No se generó el asiento contable del Pago.');
    $idComprobantePago = $asientosPago[0]['id_comprobante_diario'];

    $detallesPago = $db->query("SELECT * FROM movimiento_contable WHERE id_comprobante_diario = $idComprobantePago")->fetchAll();
    expect(count($detallesPago))->toBeGreaterThanOrEqual(2, 'El asiento de pago debe tener al menos un Debe y un Haber.');
});
