<?php
require 'vendor/autoload.php';
$repo = new App\Repositories\UsuarioRepository(App\Database\Connection::getInstance());
$permisos = $repo->getPermisos(1);
print_r($permisos);
