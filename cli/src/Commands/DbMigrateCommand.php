<?php

declare(strict_types=1);

namespace Sadi\Commands;

use PDO;
use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;

class DbMigrateCommand extends Command
{
    public function getName(): string        { return 'db:migrate'; }
    public function getDescription(): string { return 'Aplica las migraciones pendientes en database/migrations/'; }

    public function handle(Input $input, Output $output): int
    {
        $env = $this->loadEnv($input->getOption('env', ''));
        $dsn = "pgsql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}";

        try {
            $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $output->error("No se pudo conectar a la BD: " . $e->getMessage());
            return 1;
        }

        $output->info("Conectado a {$env['DB_DATABASE']}@{$env['DB_HOST']}:{$env['DB_PORT']}");

        if ($input->hasOption('fresh')) {
            $output->warn("Opción --fresh detectada. Vaciando base de datos...");
            $pdo->exec("DROP SCHEMA public CASCADE; CREATE SCHEMA public;");
            $output->success("Base de datos limpia.");
        }

        // Crear tabla de migraciones si no existe
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Obtener migraciones ya ejecutadas
        $stmt = $pdo->query("SELECT migration FROM migrations");
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $executedMap = array_flip($executed);

        // Obtener archivos de migración
        $migrationsDir = $this->basePath('database/migrations');
        if (!is_dir($migrationsDir)) {
            $output->warn("El directorio de migraciones no existe: {$migrationsDir}");
            return 0;
        }

        $files = glob("{$migrationsDir}/*.sql");
        if (!$files) {
            $output->warn("No hay archivos de migración en {$migrationsDir}");
            return 0;
        }
        sort($files);

        $pending = 0;

        foreach ($files as $file) {
            $migrationName = basename($file);

            if (isset($executedMap[$migrationName])) {
                continue;
            }

            $pending++;
            $output->info("Migrando: {$migrationName} ...");

            $sql = file_get_contents($file);

            try {
                $pdo->beginTransaction();
                $pdo->exec($sql);
                
                $insertStmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (:mig)");
                $insertStmt->execute(['mig' => $migrationName]);
                
                $pdo->commit();
                $output->success("Migrado: {$migrationName}");
            } catch (\Exception $e) {
                $pdo->rollBack();
                $output->error("Error ejecutando {$migrationName}: " . $e->getMessage());
                return 1;
            }
        }

        if ($pending === 0) {
            $output->comment("No hay migraciones pendientes.");
        } else {
            $output->line();
            $output->success("{$pending} migración(es) completadas.");
        }

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
