<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Connection;
use App\Repositories\AsientoContableRepository;
use Exception;
use PDOException;

test('se puede guardar un asiento cuadrado desde transaccion', function () {
    $db = Connection::getInstance();
    $repo = new AsientoContableRepository($db);
    
    // Preparar cuentas para el test
    $db->exec("INSERT INTO cuenta_contable (id_cuenta_contable, codigo_cuenta, denominacion_cuenta) VALUES (101, '1.1.1.01.02', 'Caja Test') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO cuenta_contable (id_cuenta_contable, codigo_cuenta, denominacion_cuenta) VALUES (102, '2.1.1.01.02', 'Por Pagar Test') ON CONFLICT DO NOTHING");

    $movimientos = [
        ['id_cuenta' => 101, 'tipo' => 'D', 'monto' => 5000.50],
        ['id_cuenta' => 102, 'tipo' => 'H', 'monto' => 5000.50]
    ];

    $fecha = date('Y-m-d');
    $concepto = 'Asiento Manual de Prueba';

    $resultado = $repo->registrarDesdeTransaccion($fecha, $concepto, $movimientos);

    expect($resultado)->toBeGreaterThan(0);

    // Validar el formato CD-YYYY-MM-XXXX
    $stmt = $db->query("SELECT * FROM comprobante_diario ORDER BY id_comprobante_diario DESC LIMIT 1");
    $comprobante = $stmt->fetch();
    expect($comprobante)->not->toBeFalse();
    expect($comprobante['concepto'])->toBe('Asiento Manual de Prueba');
    expect($comprobante['numero_comprobante'])->toMatch('/^CD-\d{4}-\d{2}-\d{4}$/');
    
    // Limpiar para otros tests
    $db->exec("DELETE FROM movimiento_contable WHERE id_comprobante_diario = {$comprobante['id_comprobante_diario']}");
    $db->exec("DELETE FROM comprobante_diario WHERE id_comprobante_diario = {$comprobante['id_comprobante_diario']}");
});

test('rechaza guardar un asiento descuadrado', function () {
    $db = Connection::getInstance();
    $repo = new AsientoContableRepository($db);

    $movimientos = [
        ['id_cuenta' => 101, 'tipo' => 'D', 'monto' => 5000.50],
        ['id_cuenta' => 102, 'tipo' => 'H', 'monto' => 5000.00] // Diferencia de 0.50
    ];

    expect(fn() => $repo->registrarDesdeTransaccion(date('Y-m-d'), 'Asiento Descuadrado', $movimientos))
        ->toThrow(Exception::class, 'El asiento contable no cuadra');
});

test('permite anulacion logica de asiento', function () {
    $db = Connection::getInstance();
    $repo = new AsientoContableRepository($db);
    
    $movimientos = [
        ['id_cuenta' => 101, 'tipo' => 'D', 'monto' => 100.00],
        ['id_cuenta' => 102, 'tipo' => 'H', 'monto' => 100.00]
    ];

    $repo->registrarDesdeTransaccion(date('Y-m-d'), 'Asiento a anular', $movimientos);
    
    $stmt = $db->query("SELECT id_comprobante_diario FROM comprobante_diario ORDER BY id_comprobante_diario DESC LIMIT 1");
    $id = (int)$stmt->fetchColumn();

    $repo->anular($id, 1);

    $stmt2 = $db->query("SELECT eliminado FROM comprobante_diario WHERE id_comprobante_diario = {$id}");
    $eliminado = $stmt2->fetchColumn();
    expect($eliminado)->toBeTrue();
    
    // Limpiar
    $db->exec("DELETE FROM movimiento_contable WHERE id_comprobante_diario = {$id}");
    $db->exec("DELETE FROM comprobante_diario WHERE id_comprobante_diario = {$id}");
});
