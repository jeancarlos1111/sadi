<?php

use App\Database\Connection;
use App\Repositories\ProveedorRepository;
use App\Controllers\BaseController;

test('Auditoría global: El borrado de entidades clave es lógico y registrable', function () {
    $db = Connection::getInstance();

    // 1. Limpiar proveedor y auditoria
    $db->exec("DELETE FROM proveedor WHERE id_proveedor = 8888");
    $db->exec("DELETE FROM auditoria_log WHERE tabla = 'proveedor'");

    // 2. Crear proveedor directamente
    $db->exec("INSERT INTO proveedor (id_proveedor, rif_proveedor, compania_proveedor, eliminado) VALUES (8888, 'J-00000000-0', 'Proveedor a Borrar', false)");

    // 3. Eliminar proveedor a través del repositorio
    $repo = new ProveedorRepository($db);
    $exito = $repo->delete(8888);

    expect($exito)->toBeTrue('El borrado debería reportar éxito.');

    // 4. Validar que no se eliminó de la base de datos (Borrado lógico)
    $proveedor = $db->query("SELECT eliminado FROM proveedor WHERE id_proveedor = 8888")->fetch();
    expect($proveedor)->not->toBeFalse('El proveedor aún debe existir en la BD.');
    expect($proveedor['eliminado'])->toBe(true, 'El proveedor debe estar marcado como eliminado.');

    // 5. Simular la traza de auditoría de BaseController
    $controllerMock = new class extends BaseController {
        public function triggerAudit(string $tabla, string $accion, int $id, array $antes, array $despues): void {
            $this->audit($tabla, $accion, $id, $antes, $despues);
        }
    };
    
    // Simular que un usuario inició sesión
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nombre'] = 'admin_test';

    $controllerMock->triggerAudit(
        'proveedor', 
        'ELIMINAR', 
        8888, 
        ['id_proveedor' => 8888, 'eliminado' => false], 
        ['id_proveedor' => 8888, 'eliminado' => true]
    );

    // 6. Validar registro en auditoria_log
    $log = $db->query("SELECT * FROM auditoria_log WHERE tabla = 'proveedor' AND id_registro = 8888")->fetch();
    expect($log)->not->toBeFalse('Debe existir un registro de auditoría.');
    expect($log['accion'])->toBe('ELIMINAR');
    expect($log['id_usuario'])->toBe(1);
    
    // Limpiar session
    unset($_SESSION['usuario_id'], $_SESSION['usuario_nombre']);
});
