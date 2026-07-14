<?php

declare(strict_types=1);

namespace Sadi\Commands;

use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;

class MakeControllerCommand extends Command
{
    public function getName(): string        { return 'make:controller'; }
    public function getDescription(): string { return 'Genera un nuevo Controller'; }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->getArgument(0);
        if ($name === '') {
            $output->error('Debes indicar el nombre. Ej: php sadi make:controller Proyectos');
            return 1;
        }

        // Si el usuario pasa "Proyectos" lo convierte a "ProyectosController"
        $className = str_ends_with($name, 'Controller') ? $name : $name . 'Controller';
        $target    = $this->basePath("src/Controllers/{$className}.php");
        $stub      = $this->basePath('cli/stubs/controller.stub');

        $created = $this->generateFromStub($stub, $target, [
            '{{ ClassName }}' => $className,
            '{{ Name }}'     => $name,
        ], $output);

        if ($created) {
            $output->success("Controller creado: src/Controllers/{$className}.php");
        }

        return $created ? 0 : 1;
    }
}
