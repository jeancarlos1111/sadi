<?php

use App\Controllers\AdminController;
use App\Repositories\InstitucionRepository;
use App\Repositories\PermisoRepository;
use App\Repositories\RolRepository;
use App\Repositories\UsuarioRepository;

test('la ruta admin/institucion muestra la vista de solo lectura', function () {
    // Simular sesión de administrador para pasar la validación del Gate
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['usuario_id'] = 1;
    $_SESSION['permisos'] = ['admin.usuarios.ver' => true, 'admin.usuarios.editar' => true];
    // Evitamos problemas de rutas relativas
    $_GET['route'] = 'admin/institucion';

    $usuarioRepo = new UsuarioRepository();
    $rolRepo = new RolRepository();
    $permisoRepo = new PermisoRepository();
    $institucionRepo = new InstitucionRepository();

    $controller = new AdminController($usuarioRepo, $rolRepo, $permisoRepo, $institucionRepo);

    ob_start();
    $controller->institucion();
    $output = ob_get_clean();

    // Verificamos que se renderice la interfaz de datos protegidos
    expect($output)->toContain('Configuración Legal y de Contacto');
    expect($output)->toContain('Editar Configuración');
    // Nos aseguramos que no hay un tag de <form> abierto para edición en la vista base
    expect($output)->not->toContain('<form action="?route=admin/institucionGuardar"');
});

test('la ruta admin/institucionEditar muestra el formulario con vista previa', function () {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['usuario_id'] = 1;
    $_SESSION['permisos'] = ['admin.usuarios.editar' => true];
    $_GET['route'] = 'admin/institucionEditar';

    $usuarioRepo = new UsuarioRepository();
    $rolRepo = new RolRepository();
    $permisoRepo = new PermisoRepository();
    $institucionRepo = new InstitucionRepository();

    $controller = new AdminController($usuarioRepo, $rolRepo, $permisoRepo, $institucionRepo);

    ob_start();
    $controller->institucionEditar();
    $output = ob_get_clean();

    // Verificamos el formulario, la previsualización y el action de guardado
    expect($output)->toContain('?route=admin/institucionGuardar');
    expect($output)->toContain('Vista Previa:');
    expect($output)->toContain('Guardar Configuración');
});
