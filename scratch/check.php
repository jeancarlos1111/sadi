<?php
require 'vendor/autoload.php';
$db = App\Database\Connection::getInstance();
$stmt = $db->query('SELECT * FROM usuario_rol WHERE id_usuario = 1');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $db->query("SELECT * FROM rol WHERE nombre = 'ADMINISTRADOR'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
