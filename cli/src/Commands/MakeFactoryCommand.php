<?php

declare(strict_types=1);

namespace Sadi\Commands;

use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;

class MakeFactoryCommand extends Command
{
    public function getName(): string { return 'make:factory'; }
    public function getDescription(): string { return 'Genera un nuevo Factory para testing y seeding'; }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->getArgument(0);
        if ($name === '') {
            $output->error('Debes indicar el nombre del Factory. Ej: php sadi make:factory PersonalFactory');
            return 1;
        }

        if (!str_ends_with($name, 'Factory')) {
            $name .= 'Factory';
        }

        $baseName = str_replace('Factory', '', $name);
        $modelName = $baseName;
        $repositoryName = $baseName . 'Repository';

        $output->title("Generando factory: $name");

        $factoryFile = "database/factories/{$name}.php";
        
        $result = $this->generateFromStub(
            $this->basePath('cli/stubs/factory.stub'),
            $this->basePath($factoryFile),
            [
                '{{FactoryName}}' => $name,
                '{{ModelName}}' => $modelName,
                '{{RepositoryName}}' => $repositoryName,
            ],
            $output
        );

        if ($result) {
            $output->success("Factory creado exitosamente: {$factoryFile}");
            return 0;
        }

        return 1;
    }
}
