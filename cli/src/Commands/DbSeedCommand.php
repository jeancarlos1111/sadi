<?php

declare(strict_types=1);

namespace Sadi\Commands;

use PDO;
use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;
use App\Database\Connection;

class DbSeedCommand extends Command
{
    public function getName(): string        { return 'db:seed'; }
    public function getDescription(): string { return 'Ejecuta los seeders PHP de database/'; }

    public function handle(Input $input, Output $output): int
    {
        $force = $input->hasOption('force');
        // Load the correct environment DB Connection
        $env = $this->loadEnv($input->getOption('env', ''));

        if (($env['APP_ENV'] ?? '') === 'production' && !$force) {
            $output->warn("Estás en un entorno de producción (APP_ENV=production).");
            $output->warn("Para ejecutar los seeders en producción, usa la opción --force.");
            return 1;
        }

        $dsn = "pgsql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}";

        try {
            $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            Connection::setInstance($pdo);
            $output->info("Conectado a {$env['DB_DATABASE']}@{$env['DB_HOST']}:{$env['DB_PORT']}");
        } catch (\PDOException $e) {
            $output->error("No se pudo conectar a la BD: " . $e->getMessage());
            return 1;
        }

        $className = $input->getOption('class');

        if ($className) {
            if (!str_contains($className, '\\')) {
                $className = 'Database\\Seeders\\' . $className;
            }

            if (!class_exists($className)) {
                $output->error("No se encontró la clase seeder: {$className}");
                return 1;
            }

            $output->title("Ejecutando seeder: {$className}");
            try {
                $seeder = new $className();
                if (method_exists($seeder, 'run')) {
                    $seeder->run();
                }
                $output->success("{$className} completado.");
                return 0;
            } catch (\Throwable $e) {
                $output->error("El seeder {$className} falló: " . $e->getMessage());
                return 1;
            }
        }

        $output->title("Ejecutando seeders");

        // Execute default class based seeder if it exists
        $defaultSeeder = 'Database\\Seeders\\DatabaseSeeder';
        if (class_exists($defaultSeeder)) {
            $output->info("  → Ejecutando {$defaultSeeder}...");
            try {
                $seeder = new $defaultSeeder();
                if (method_exists($seeder, 'run')) {
                    $seeder->run();
                }
                $output->success("{$defaultSeeder} completado.");
            } catch (\Throwable $e) {
                $output->error("{$defaultSeeder} falló: " . $e->getMessage());
                return 1;
            }
        }

        $output->line();
        $output->success("Seeding completado correctamente.");
        return 0;
    }

    private function loadEnv(string $envFile = ''): array
    {
        $base    = $this->basePath();
        $envPath = $envFile ? $base . '/' . $envFile : $base . '/.env';
        $config  = parse_ini_file($envPath) ?: [];

        return array_merge([
            'DB_HOST'     => '127.0.0.1',
            'DB_PORT'     => '5432',
            'DB_DATABASE' => 'sadi_db',
            'DB_USERNAME' => 'sadi',
            'DB_PASSWORD' => '',
        ], $config);
    }
}
