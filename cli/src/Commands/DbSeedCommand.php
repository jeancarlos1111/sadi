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
        // Load the correct environment DB Connection
        $env = $this->loadEnv($input->getOption('env', ''));
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

        $seedDir = $this->basePath('database');
        $files   = glob($seedDir . '/seed_*.php') ?: [];

        if (empty($files)) {
            $output->warn("No se encontraron archivos seed_*.php en database/");
            return 0;
        }

        $output->title("Ejecutando seeders");

        foreach ($files as $file) {
            $name = basename($file);
            $output->info("  → Ejecutando {$name}...");

            try {
                require_once $file;
                $output->success("{$name} completado.");
            } catch (\Throwable $e) {
                $output->error("{$name} falló: " . $e->getMessage());
                return 1;
            }
        }

        $output->line();
        $output->success(count($files) . " seeder(s) ejecutados correctamente.");
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
