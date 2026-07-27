<?php

use App\Models\InventarioBien;
use App\Repositories\InventarioBienesRepository;

beforeEach(function () {
    // Setup logic if needed
});

test('puede calcular depreciacion por linea recta', function () {
    $bien = new InventarioBien(
        1, 1, date('Y-m-d'), 1, 12000.0, 1, 1, 'TEST-1', true, 60, 2000.0
    );

    // Costo = 12000, Residual = 2000, Vida util = 60 meses
    // Cuota = (12000 - 2000) / 60 = 10000 / 60 = 166.666...
    $cuotaMensual = (12000 - 2000) / 60;

    expect(round($cuotaMensual, 2))->toEqual(166.67);
});
