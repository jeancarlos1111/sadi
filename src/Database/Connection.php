<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function setInstance(PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $baseDir = dirname(__DIR__, 2);
            $envFile = $baseDir . '/.env';

            $config = [
                'DB_CONNECTION' => 'sqlite',
            ];

            if (file_exists($envFile)) {
                $envConfig = @parse_ini_file($envFile);
                if ($envConfig !== false) {
                    $config = array_merge($config, $envConfig);
                }
            }

            try {
                if (($config['DB_CONNECTION'] ?? 'sqlite') === 'pgsql') {
                    $host = $config['DB_HOST'] ?? '127.0.0.1';
                    $port = $config['DB_PORT'] ?? '5432';
                    $dbname = $config['DB_DATABASE'] ?? 'sadi_db';
                    $user = $config['DB_USERNAME'] ?? 'sadi';
                    $password = $config['DB_PASSWORD'] ?? 'sadi';

                    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
                    self::$instance = new PDO($dsn, $user, $password);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                } else {
                    $dbPath = $baseDir . '/database/sadi.sqlite';
                    $dsn = "sqlite:" . $dbPath;

                    self::$instance = new PDO($dsn);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                    self::$instance->exec("PRAGMA busy_timeout = 5000;");

                    self::$instance->sqliteCreateFunction('ILIKE', function ($a, $b) {
                        return stripos($a, str_replace('%', '', $b)) !== false ? 1 : 0;
                    }, 2);

                    self::$instance->sqliteCreateFunction('modulo_presupuesto_buscar_formatear_estructura_presupuestaria', function ($id) {
                        return "ESTRUCTURA-MOCK-" . $id;
                    }, 1);
                }
            } catch (PDOException $e) {
                die("Error de conexión a la base de datos: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
