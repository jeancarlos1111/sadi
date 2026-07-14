<?php

declare(strict_types=1);

namespace Sadi\Commands;

use Sadi\Command;
use Sadi\Console\Input;
use Sadi\Console\Output;

class RouteListCommand extends Command
{
    public function getName(): string        { return 'route:list'; }
    public function getDescription(): string { return 'Lista las rutas registradas en public/index.php'; }

    public function handle(Input $input, Output $output): int
    {
        $indexPath = $this->basePath('public/index.php');

        if (!file_exists($indexPath)) {
            $output->error("No se encontró public/index.php");
            return 1;
        }

        $content = file_get_contents($indexPath);

        // Detectar mapa de rutas: 'ruta' => 'Controller@method'
        preg_match_all(
            "/['\"]([a-z0-9_\/]+)['\"]\s*=>\s*['\"]([A-Za-z]+)(?:@([a-z]+))?['\"]/",
            $content,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            // Intenta también la sintaxis ?route=modulo/accion
            preg_match_all(
                "/case\s+['\"]([a-z0-9_\/]+)['\"]/",
                $content,
                $matches2,
                PREG_SET_ORDER
            );

            if (empty($matches2)) {
                $output->warn("No se detectaron rutas en public/index.php");
                return 0;
            }

            $output->title("Rutas detectadas (switch/case)");
            $rows = array_map(fn($m) => ['route' => $m[1], 'tipo' => 'case'], $matches2);
            $output->table(['Ruta (?route=)', 'Tipo'], $rows);
            return 0;
        }

        $output->title("Rutas registradas");
        $rows = array_map(fn($m) => [
            'Ruta'        => $m[1],
            'Controlador' => $m[2],
            'Método'      => $m[3] ?? 'index',
        ], $matches);

        $output->table(['Ruta', 'Controlador', 'Método'], $rows);
        $output->line();
        $output->comment("  Total: " . count($rows) . " rutas.");
        return 0;
    }
}
