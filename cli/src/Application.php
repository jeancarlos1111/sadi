<?php

declare(strict_types=1);

namespace Sadi;

use Sadi\Console\Input;
use Sadi\Console\Output;

class Application
{
    /** @var Command[] */
    private array $commands = [];

    public function register(Command $command): static
    {
        $this->commands[$command->getName()] = $command;
        return $this;
    }

    public function run(array $argv): int
    {
        $input  = new Input($argv);
        $output = new Output();

        $commandName = $input->getCommand();

        if ($commandName === '' || $commandName === 'list') {
            $this->printList($output);
            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            $output->error("Comando desconocido: \"$commandName\". Ejecuta `php sadi list`.");
            return 1;
        }

        return $this->commands[$commandName]->handle($input, $output);
    }

    private function printList(Output $output): void
    {
        $output->line();
        $output->info("  ███████╗ █████╗ ██████╗ ██╗");
        $output->info("  ██╔════╝██╔══██╗██╔══██╗██║");
        $output->info("  ███████╗███████║██║  ██║██║");
        $output->info("  ╚════██║██╔══██║██║  ██║██║");
        $output->info("  ███████║██║  ██║██████╔╝██║");
        $output->info("  ╚══════╝╚═╝  ╚═╝╚═════╝ ╚═╝  CLI v1.0");
        $output->line();
        $output->comment("  Sistema Administrativo De Información");
        $output->line();

        $output->title('Comandos disponibles');

        $rows = [];
        foreach ($this->commands as $name => $command) {
            $rows[] = ['Comando' => $name, 'Descripción' => $command->getDescription()];
        }

        $output->table(['Comando', 'Descripción'], $rows);
        $output->line();
        $output->comment("  Uso: php sadi <comando> [argumentos] [--opciones]");
        $output->line();
    }
}
