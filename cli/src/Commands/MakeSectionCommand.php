<?php

declare(strict_types=1);

namespace Sadi\Commands;

use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;

class MakeSectionCommand extends Command
{
    public function getName(): string        { return 'make:section'; }
    public function getDescription(): string { return 'Genera el scaffold completo de una sección (modelo, repositorio, controller, migración y vistas)'; }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->getArgument(0);
        if ($name === '') {
            $output->error('Debes indicar el nombre de la sección. Ej: php sadi make:section Contrato');
            $output->comment('  Opciones:');
            $output->comment('    --table=nombre_tabla     (default: nombre en snake_case)');
            $output->comment('    --module=nombre_modulo   (prefijo de carpeta de vistas, ej: contratos)');
            return 1;
        }

        // Derivar nombres automáticamente
        $className   = ucfirst($name);
        $table       = $input->getOption('table',  $this->toSnakeCase($name));
        $module      = $input->getOption('module', $this->toSnakeCase($name));
        $routePrefix = $module;

        $output->title("Generando sección: $className");

        $generated = [];
        $skipped   = [];

        // 1. Modelo
        $result = $this->generate('model', "src/Models/{$className}.php", [
            '{{ ClassName }}' => $className,
        ], $output);
        $result ? $generated[] = "src/Models/{$className}.php" : $skipped[] = "src/Models/{$className}.php";

        // 2. Repository
        $repoClass = "{$className}Repository";
        $result = $this->generate('repository', "src/Repositories/{$repoClass}.php", [
            '{{ ClassName }}' => $repoClass,
            '{{ ModelName }}' => $className,
            '{{ TableName }}' => $table,
        ], $output);
        $result ? $generated[] = "src/Repositories/{$repoClass}.php" : $skipped[] = "src/Repositories/{$repoClass}.php";

        // 3. Controller (con CRUD completo)
        $ctrlClass = "{$className}Controller";
        $result = $this->generate('controller_section', "src/Controllers/{$ctrlClass}.php", [
            '{{ ClassName }}'    => $ctrlClass,
            '{{ ModelClass }}'   => $className,
            '{{ RepoClass }}'    => $repoClass,
            '{{ routePrefix }}'  => $routePrefix,
            '{{ viewFolder }}'   => $module,
            '{{ Name }}'         => $name,
        ], $output);
        $result ? $generated[] = "src/Controllers/{$ctrlClass}.php" : $skipped[] = "src/Controllers/{$ctrlClass}.php";

        // 4. Migración SQL
        $migrationFile = "database/migrations/{$table}.sql";
        $result = $this->generate('migration', $migrationFile, [
            '{{ TableName }}' => $table,
            '{{ Date }}'      => date('Y-m-d H:i:s'),
        ], $output);
        $result ? $generated[] = $migrationFile : $skipped[] = $migrationFile;

        // 5. Vistas (index.phtml y form.phtml)
        $viewDir = "views/{$module}";
        $viewReplacements = [
            '{{ ClassName }}'    => $className,
            '{{ routePrefix }}'  => $routePrefix,
            '{{ viewFolder }}'   => $module,
        ];

        $result = $this->generate('view_index', "{$viewDir}/index.phtml", $viewReplacements, $output);
        $result ? $generated[] = "{$viewDir}/index.phtml" : $skipped[] = "{$viewDir}/index.phtml";

        $result = $this->generate('view_form', "{$viewDir}/form.phtml", $viewReplacements, $output);
        $result ? $generated[] = "{$viewDir}/form.phtml" : $skipped[] = "{$viewDir}/form.phtml";

        // Resumen final
        $output->line();
        $output->title('Resumen');

        if (!empty($generated)) {
            $output->success(count($generated) . ' archivo(s) creado(s):');
            foreach ($generated as $f) {
                $output->line("    \033[32m+\033[0m $f");
            }
        }

        if (!empty($skipped)) {
            $output->line();
            $output->warn(count($skipped) . ' archivo(s) ya existían (no modificados):');
            foreach ($skipped as $f) {
                $output->line("    \033[33m~\033[0m $f");
            }
        }

        $output->line();
        $output->info("  Próximos pasos:");
        $output->comment("  1. Añade la tabla en database/schema.sql (o aplica la migración en database/migrations/{$table}.sql)");
        $output->comment("  2. Registra la ruta en public/index.php:");
        $output->comment("     '{$routePrefix}/index' => '{$ctrlClass}@index'");
        $output->comment("  3. Completa el modelo, repositorio y vistas con la lógica real.");

        return 0;
    }

    private function generate(string $stubName, string $relativePath, array $replacements, Output $output): bool
    {
        $stub   = $this->basePath("cli/stubs/{$stubName}.stub");
        $target = $this->basePath($relativePath);

        return $this->generateFromStub($stub, $target, $replacements, $output);
    }

    private function toSnakeCase(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}
