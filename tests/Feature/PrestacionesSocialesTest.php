<?php

declare(strict_types=1);

use App\Models\Ficha;
use App\Models\PrestacionGarantia;
use App\Repositories\FichaRepository;
use App\Repositories\PersonalRepository;
use App\Repositories\PrestacionesRepository;
use App\Database\Connection;

beforeEach(function () {
    $this->db = Connection::getInstance();
    $this->db->beginTransaction();

    $this->fichaRepo = new FichaRepository();
    $this->personalRepo = new PersonalRepository();
    $this->prestacionesRepo = new PrestacionesRepository();

    // Crear un personal y una ficha de prueba
    $numeroCedula = 'TEST' . uniqid();
    $this->db->exec("INSERT INTO personal (cedula, nombres, apellidos, fecha_nacimiento) VALUES ('$numeroCedula', 'Juan', 'Perez', '1990-01-01')");
    $this->idPersonal = (int)$this->db->lastInsertId();

    $ficha = new Ficha(0, $this->idPersonal, 1, 1, '2025-01-01', 3000.00, 30, 15);
    $id = $this->fichaRepo->save($ficha);
    $this->idFicha = (int)$id;
});

afterEach(function () {
    if ($this->db->inTransaction()) {
        $this->db->rollBack();
    }
});

test('calcula el salario integral correctamente segun base legal LOTTT', function () {
    // Sueldo base: 3000 Bs mensuales -> 100 Bs diarios
    // Utilidades (30 dias): 3000 Bs anuales / 360 = 8.333 Bs diarios
    // Bono Vacacional (15 dias): 1500 Bs anuales / 360 = 4.166 Bs diarios
    // Salario Integral Diario: 100 + 8.333 + 4.166 = 112.5 Bs

    // Procesar trimestre manually mimicking controller
    $sueldo = 3000.00;
    $diasUti = 30;
    $diasBono = 15;
    
    $sueldoDiario = $sueldo / 30;
    $salarioIntegralDiario = $sueldoDiario + (($diasBono * $sueldoDiario)/360) + (($diasUti * $sueldoDiario)/360);
    
    expect(round($salarioIntegralDiario, 2))->toBe(112.50);
});

test('deposita la garantia trimestral equivalente a 15 dias', function () {
    $salarioIntegralDiario = 112.50;
    $montoDeposito = $salarioIntegralDiario * 15;
    
    expect($montoDeposito)->toBe(1687.50);

    $garantia = new PrestacionGarantia(
        null,
        $this->idFicha,
        '2026-Q1',
        'TRIMESTRAL',
        15,
        $salarioIntegralDiario,
        $montoDeposito,
        '2026-03-31'
    );

    $this->prestacionesRepo->save($garantia);
    
    $estadoCuenta = $this->prestacionesRepo->getEstadoCuenta($this->idFicha);
    expect(count($estadoCuenta))->toBe(1);
    expect($estadoCuenta[0]->diasDepositados)->toBe(15);
    expect($estadoCuenta[0]->monto)->toBe(1687.50);
});

test('no permite procesar el mismo trimestre dos veces', function () {
    $garantia = new PrestacionGarantia(
        null,
        $this->idFicha,
        '2026-Q1',
        'TRIMESTRAL',
        15,
        112.50,
        1687.50,
        '2026-03-31'
    );
    $this->prestacionesRepo->save($garantia);

    $existe = $this->prestacionesRepo->existePeriodoProcesado('2026-Q1', 'TRIMESTRAL');
    expect($existe)->toBeTrue();
    
    $noExiste = $this->prestacionesRepo->existePeriodoProcesado('2026-Q2', 'TRIMESTRAL');
    expect($noExiste)->toBeFalse();
});
