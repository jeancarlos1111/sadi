<?php
require_once dirname(__DIR__) . '/src/Database/Connection.php';
$db = App\Database\Connection::getInstance();
$fichas = $db->query('SELECT * FROM ficha')->fetchAll();
header('Content-Type: application/json');
echo json_encode([
    'db_path' => dirname(__DIR__, 2) . '/database/sadi.sqlite',
    'fichas' => $fichas
]);
