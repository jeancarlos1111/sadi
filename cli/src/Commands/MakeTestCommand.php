<?php

declare(strict_types=1);

namespace Sadi\Commands;

use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;

class MakeTestCommand extends Command
{
    public function getName(): string        { return 'make:test'; }
    public function getDescription(): string { return 'Genera un Feature Test (Pest PHP)'; }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->getArgument(0);
        if ($name === '') {
            $output->error('Debes indicar el nombre. Ej: php sadi make:test Formulacion');
            return 1;
        }

        $className = str_ends_with($name, 'Test') ? $name : $name . 'Test';
        $isUnit    = $input->hasOption('unit');
        $folder    = $isUnit ? 'Unit' : 'Feature';
        $target    = $this->basePath("tests/{$folder}/{$className}.php");
        $stub      = $this->basePath('cli/stubs/test.stub');

        $created = $this->generateFromStub($stub, $target, [
            '{{ TestName }}' => $name,
        ], $output);

        if ($created) {
            $output->success("Test creado: tests/{$folder}/{$className}.php");
        }

        return $created ? 0 : 1;
    }
}
