<?php

use App\Models\PresupuestoGasto;
use App\Models\PAC;

test('el modelo PresupuestoGasto se puede instanciar y serializar a array', function () {
    $modelo = new PresupuestoGasto(1, 1, 1000.50, 200.00, 150.00, 50.00, 50.00, 1, 1, false, 1);
    
    $array = $modelo->toArray();
    
    expect($array['idEstructura'])->toEqual(1);
    expect($array['montoAsignado'])->toEqual(1000.50);
});

test('el modelo PAC puede reconstruirse desde un array', function () {
    $data = [
        'id' => 10,
        'id_proyecto' => 1,
        'id_accion_centralizada' => null,
        'id_articulo' => 5,
        'cantidad_anual' => 10,
        'trim_1' => 2,
        'trim_2' => 3,
        'trim_3' => 2,
        'trim_4' => 3,
        'costo_estimado' => 5000.00,
        'estatus' => 'Pendiente'
    ];
    
    $pac = PAC::fromArray($data);
    
    expect($pac->id)->toEqual(10);
    expect($pac->id_articulo)->toEqual(5);
    expect($pac->costo_estimado)->toEqual(5000.00);
});
