<?php

declare(strict_types=1);

namespace Sadi\Commands;

use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;

class MakeSeederCommand extends Command
{
    public function getName(): string { return 'make:seeder'; }
    public function getDescription(): string { return 'Genera un nuevo Seeder de base de datos'; }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->getArgument(0);
        if ($name === '') {
            $output->error('Debes indicar el nombre del Seeder. Ej: php sadi make:seeder RolesSeeder');
            return 1;
        }

        if (!str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $output->title("Generando seeder: $name");

        $seederFile = "database/seeders/{$name}.php";
        
        $result = $this->generateFromStub(
            $this->basePath('cli/stubs/seeder.stub'),
            $this->basePath($seederFile),
            [
                '{{SeederName}}' => $name,
            ],
            $output
        );

        if ($result) {
            $output->success("Seeder creado exitosamente: {$seederFile}");
            return 0;
        }

        return 1;
    }
}
