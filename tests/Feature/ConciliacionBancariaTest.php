<?php

declare(strict_types=1);

use App\Controllers\BancoController;
use App\Models\CuentaBancaria;
use App\Models\MovimientoBancario;
use App\Repositories\CuentaBancariaRepository;
use App\Repositories\MovimientoBancarioRepository;
use App\Repositories\SolicitudPagoRepository;
use App\Database\Connection;

beforeEach(function () {
    try {
        $this->db = Connection::getInstance();
        $this->db->beginTransaction();

        $this->movRepo = new MovimientoBancarioRepository();
        $this->ctaRepo = new CuentaBancariaRepository();
        $this->solRepo = new SolicitudPagoRepository();

        $stmt = $this->db->query("SELECT id_banco FROM banco LIMIT 1");
        $idBanco = $stmt->fetchColumn();
        if (!$idBanco) {
            $this->db->exec("INSERT INTO banco (nombre_banco) VALUES ('Banco de Pruebas')");
            $idBanco = $this->db->lastInsertId();
        }

        $numeroCta = 'TEST' . uniqid();
        $this->db->exec("INSERT INTO cta_bancaria (id_banco, numero_cta_bancaria, id_cuenta_contable) VALUES ($idBanco, '$numeroCta', 1)");
        $this->idCuenta = (int)$this->db->lastInsertId();

        $stmt = $this->db->query("SELECT id_tipo_operacion_bancaria FROM tipo_operacion_bancaria LIMIT 1");
        $this->idTipoOp = (int)$stmt->fetchColumn();
        if (!$this->idTipoOp) {
            $this->db->exec("INSERT INTO tipo_operacion_bancaria (nombre_tipo_operacion_bancaria, acronimo_tipo_operacion_bancaria) VALUES ('DEPOSITO', 'DEP')");
            $this->idTipoOp = (int)$this->db->lastInsertId();
        }
        
        $this->movRepo->save(new MovimientoBancario($this->idCuenta, $this->idTipoOp, 1000.00, '2026-07-15', 'DEP01'));
        $this->movRepo->save(new MovimientoBancario($this->idCuenta, $this->idTipoOp, 500.00, '2026-07-18', 'DEP02'));
        $this->movRepo->save(new MovimientoBancario($this->idCuenta, $this->idTipoOp, -200.00, '2026-07-20', 'CHQ01'));
        
        $_SESSION['usuario_id'] = 1;
        $this->controller = new BancoController($this->movRepo, $this->ctaRepo, $this->solRepo);
    } catch (\Throwable $e) {
        echo "BEFORE EACH ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
});

afterEach(function () {
    if ($this->db->inTransaction()) {
        $this->db->rollBack();
    }
});

test('puede calcular correctamente el saldo en libros', function () {
    try {
        $saldo = $this->movRepo->getSaldoLibros($this->idCuenta, '2026-07-31');
        expect($saldo)->toBe(1300.00); 
    } catch (\Throwable $e) {
        echo "TEST 1 ERROR: " . $e->getMessage() . "\n";
        throw $e;
    }
});

test('detecta movimientos no conciliados', function () {
    $movimientos = $this->movRepo->getMovimientosParaConciliar($this->idCuenta, '2026-07-31', '2026-07-31');
    expect(count($movimientos))->toBe(3);
    
    $this->movRepo->procesarConciliacionMasiva($this->idCuenta, '2026-06-30', [(int)$movimientos[0]['id_movimiento_bancario']]);
    
    $movimientosNuevos = $this->movRepo->getMovimientosParaConciliar($this->idCuenta, '2026-07-31', '2026-07-31');
    expect(count($movimientosNuevos))->toBe(2);
});

test('falla si el cuadre es incorrecto', function () {
    $saldoBanco = 1000.00;
    $saldoLibros = $this->movRepo->getSaldoLibros($this->idCuenta, '2026-07-31');
    $movimientosEnPantalla = $this->movRepo->getMovimientosParaConciliar($this->idCuenta, '2026-07-31', '2026-07-31');
    
    $sumaNoConciliados = 0.0;
    foreach ($movimientosEnPantalla as $mov) {
        $sumaNoConciliados += (float)$mov['monto'];
    }

    $saldoCalculado = $saldoBanco + $sumaNoConciliados;
    expect(abs($saldoCalculado - $saldoLibros) > 0.01)->toBeTrue();
});

test('permite guardar la conciliacion si el cuadre es exacto', function () {
    $movimientos = $this->movRepo->getMovimientosParaConciliar($this->idCuenta, '2026-07-31', '2026-07-31');
    $idMovimiento1000 = null;
    foreach ($movimientos as $m) {
        if ((float)$m['monto'] === 1000.00) {
            $idMovimiento1000 = $m['id_movimiento_bancario'];
            break;
        }
    }

    $saldoBanco = 1000.00;
    $saldoLibros = $this->movRepo->getSaldoLibros($this->idCuenta, '2026-07-31');
    $sumaNoConciliados = 500.00 - 200.00;
    $saldoCalculado = $saldoBanco + $sumaNoConciliados;
    
    expect(abs($saldoCalculado - $saldoLibros))->toBeLessThan(0.01);
    
    $this->movRepo->procesarConciliacionMasiva($this->idCuenta, '2026-07-31', [(int)$idMovimiento1000]);
    
    $movGuardado = $this->movRepo->find((int)$idMovimiento1000);
    expect($movGuardado->conciliado)->toBeTrue();
    expect($movGuardado->fechaConciliacion)->toBe('2026-07-31');
});
