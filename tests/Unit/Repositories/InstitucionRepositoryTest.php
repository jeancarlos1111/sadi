<?php

use App\Models\Institucion;
use App\Repositories\InstitucionRepository;

beforeEach(function () {
    $this->repo = new InstitucionRepository();
});

test('getConfig devuelve la institución por defecto si no existe', function () {
    $config = $this->repo->getConfig();
    
    expect($config)->toBeInstanceOf(Institucion::class);
    expect($config->id_institucion)->toBe(1);
    expect($config->nombre)->toBe('Alcaldía de Prueba');
    expect($config->rif)->toBe('G-12345678-9');
});

test('saveConfig guarda y actualiza la información correctamente', function () {
    $nuevaConfig = Institucion::fromArray([
        'id_institucion' => 1,
        'nombre' => 'Alcaldía de Prueba',
        'rif' => 'G-12345678-9',
        'direccion' => 'Av. Principal',
        'telefono' => '0212-5555555',
        'correo' => 'contacto@alcaldia.gob.ve',
        'maxima_autoridad' => 'Juan Pérez',
        'cargo_autoridad' => 'Alcalde',
        'base_legal' => 'Gaceta 1234',
        'codigo_onapre' => '0000',
        'logo_path' => '/uploads/logo.png'
    ]);

    $this->repo->saveConfig($nuevaConfig);

    $guardada = $this->repo->getConfig();

    expect($guardada->nombre)->toBe('Alcaldía de Prueba');
    expect($guardada->rif)->toBe('G-12345678-9');
    expect($guardada->telefono)->toBe('0212-5555555');
    expect($guardada->logo_path)->toBe('/uploads/logo.png');
});
