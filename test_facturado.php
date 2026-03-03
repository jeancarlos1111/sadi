<?php
require 'src/Database/Connection.php';
$pdo = \App\Database\Connection::get();

$stmt = $pdo->query("SELECT * FROM documento WHERE id_tipo_documento = 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
