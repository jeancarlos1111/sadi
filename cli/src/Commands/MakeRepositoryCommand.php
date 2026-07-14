<?php

declare(strict_types=1);

namespace Sadi\Commands;

use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;

class MakeRepositoryCommand extends Command
{
    public function getName(): string        { return 'make:repository'; }
    public function getDescription(): string { return 'Genera un nuevo Repository'; }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->getArgument(0);
        if ($name === '') {
            $output->error('Debes indicar el nombre. Ej: php sadi make:repository Proyecto');
            return 1;
        }

        $className = str_ends_with($name, 'Repository') ? $name : $name . 'Repository';
        $table     = $input->getOption('table', strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)));
        $target    = $this->basePath("src/Repositories/{$className}.php");
        $stub      = $this->basePath('cli/stubs/repository.stub');

        $created = $this->generateFromStub($stub, $target, [
            '{{ ClassName }}' => $className,
            '{{ ModelName }}' => $name,
            '{{ TableName }}' => $table,
        ], $output);

        if ($created) {
            $output->success("Repository creado: src/Repositories/{$className}.php");
            $output->comment("  Tip: usa --table=nombre_tabla para especificar la tabla.");
        }

        return $created ? 0 : 1;
    }
}
