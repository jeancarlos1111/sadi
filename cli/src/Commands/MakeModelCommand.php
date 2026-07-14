<?php

declare(strict_types=1);

namespace Sadi\Commands;

use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;

class MakeModelCommand extends Command
{
    public function getName(): string        { return 'make:model'; }
    public function getDescription(): string { return 'Genera un nuevo Model (readonly DTO)'; }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->getArgument(0);
        if ($name === '') {
            $output->error('Debes indicar el nombre del modelo. Ej: php sadi make:model Proyecto');
            return 1;
        }

        $target = $this->basePath("src/Models/{$name}.php");
        $stub   = $this->basePath('cli/stubs/model.stub');

        $created = $this->generateFromStub($stub, $target, [
            '{{ ClassName }}' => $name,
        ], $output);

        if ($created) {
            $output->success("Modelo creado: src/Models/{$name}.php");
        }

        return $created ? 0 : 1;
    }
}
