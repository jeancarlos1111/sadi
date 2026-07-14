<?php

declare(strict_types=1);

namespace Sadi;

use Sadi\Console\Input;
use Sadi\Console\Output;

abstract class Command
{
    /** Nombre del comando, ej: "make:model" */
    abstract public function getName(): string;

    /** Descripción corta que aparece en `sadi list` */
    abstract public function getDescription(): string;

    /** Lógica de ejecución del comando */
    abstract public function handle(Input $input, Output $output): int;

    /**
     * Lee el contenido de un stub, reemplaza placeholders y escribe el archivo destino.
     * Retorna false si el destino ya existe.
     */
    protected function generateFromStub(
        string $stubPath,
        string $targetPath,
        array $replacements,
        Output $output
    ): bool {
        if (file_exists($targetPath)) {
            $output->warn("El archivo ya existe: $targetPath");
            return false;
        }

        $stub    = file_get_contents($stubPath);
        $content = str_replace(array_keys($replacements), array_values($replacements), $stub);

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($targetPath, $content);
        return true;
    }

    /** Devuelve la ruta raíz del proyecto (donde está composer.json) */
    protected function basePath(string $path = ''): string
    {
        $root = dirname(__DIR__, 2); // cli/src/Command.php → sube 2 niveles → raíz
        return $path ? $root . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $root;
    }
}
