<?php
require_once 'vendor/autoload.php';
$env = parse_ini_file('.env.testing');
$dsn = "pgsql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}";
$pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
App\Database\Connection::setInstance($pdo);
require_once 'database/seed_maestros.php';
