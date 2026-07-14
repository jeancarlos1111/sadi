<?php

declare(strict_types=1);

namespace Sadi\Commands;

use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;

class TestCommand extends Command
{
    public function getName(): string        { return 'test'; }
    public function getDescription(): string { return 'Ejecuta la suite de pruebas con Pest PHP'; }

    public function handle(Input $input, Output $output): int
    {
        $pestBin = $this->basePath('vendor/bin/pest');

        if (!file_exists($pestBin)) {
            $output->error("Pest PHP no está instalado. Ejecuta: composer require pestphp/pest --dev");
            return 1;
        }

        $args = [];

        if ($filter = $input->getOption('filter')) {
            $args[] = "--filter=" . escapeshellarg($filter);
        }

        if ($input->hasOption('coverage')) {
            $args[] = '--coverage';
        }

        if ($input->hasOption('parallel')) {
            $args[] = '--parallel';
        }

        $cmd = 'php ' . escapeshellarg($pestBin) . ' ' . implode(' ', $args);
        $output->info("Ejecutando: $cmd");
        $output->line();

        passthru($cmd, $exitCode);

        return $exitCode;
    }
}
