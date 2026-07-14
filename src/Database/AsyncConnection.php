<?php

declare(strict_types=1);

namespace App\Database;

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;

class AsyncConnection
{
    private static ?PostgresConnectionPool $pool = null;

    private function __construct()
    {
    }

    public static function getPool(): PostgresConnectionPool
    {
        if (self::$pool === null) {
            $baseDir = dirname(__DIR__, 2);
            $envFile = $baseDir . '/.env';

            $config = [];
            if (file_exists($envFile)) {
                $envConfig = @parse_ini_file($envFile);
                if ($envConfig !== false) {
                    $config = $envConfig;
                }
            }

            $host = $config['DB_HOST'] ?? '127.0.0.1';
            $port = $config['DB_PORT'] ?? '5432';
            $dbname = $config['DB_DATABASE'] ?? 'sadi_db';
            $user = $config['DB_USERNAME'] ?? 'sadi';
            $password = $config['DB_PASSWORD'] ?? 'sadi';

            $connectionString = "host=$host port=$port user=$user password=$password dbname=$dbname";
            $pgConfig = PostgresConfig::fromString($connectionString);

            self::$pool = new PostgresConnectionPool($pgConfig);
        }

        return self::$pool;
    }
}
