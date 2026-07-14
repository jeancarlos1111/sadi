<?php

declare(strict_types=1);

namespace Sadi\Console;

class Output
{
    private const COLORS = [
        'reset'   => "\033[0m",
        'bold'    => "\033[1m",
        'green'   => "\033[32m",
        'yellow'  => "\033[33m",
        'cyan'    => "\033[36m",
        'red'     => "\033[31m",
        'gray'    => "\033[90m",
        'white'   => "\033[97m",
        'bg_blue' => "\033[44m",
    ];

    public static function line(string $text = ''): void
    {
        echo $text . PHP_EOL;
    }

    public static function success(string $text): void
    {
        echo self::colorize(' ✓ ', 'bg_blue') . ' ' . self::colorize($text, 'green') . PHP_EOL;
    }

    public static function error(string $text): void
    {
        echo self::colorize(' ERROR ', 'red') . ' ' . self::colorize($text, 'red') . PHP_EOL;
    }

    public static function warn(string $text): void
    {
        echo self::colorize(' WARN ', 'yellow') . ' ' . self::colorize($text, 'yellow') . PHP_EOL;
    }

    public static function info(string $text): void
    {
        echo self::colorize($text, 'cyan') . PHP_EOL;
    }

    public static function comment(string $text): void
    {
        echo self::colorize($text, 'gray') . PHP_EOL;
    }

    public static function title(string $text): void
    {
        echo PHP_EOL . self::colorize($text, 'bold') . PHP_EOL;
        echo self::colorize(str_repeat('─', mb_strlen($text)), 'gray') . PHP_EOL;
    }

    public static function table(array $headers, array $rows): void
    {
        // Calculate column widths
        $widths = array_map('mb_strlen', $headers);
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, mb_strlen((string)$cell));
            }
        }

        $border = '┼' . implode('┼', array_map(fn($w) => str_repeat('─', $w + 2), $widths)) . '┼';
        $topBorder = '┌' . implode('┬', array_map(fn($w) => str_repeat('─', $w + 2), $widths)) . '┐';
        $bottomBorder = '└' . implode('┴', array_map(fn($w) => str_repeat('─', $w + 2), $widths)) . '┘';

        echo $topBorder . PHP_EOL;

        // Header row
        $headerRow = '│';
        foreach ($headers as $i => $header) {
            $headerRow .= ' ' . self::colorize(str_pad($header, $widths[$i]), 'bold') . ' │';
        }
        echo $headerRow . PHP_EOL;
        echo $border . PHP_EOL;

        // Data rows
        foreach ($rows as $row) {
            $line = '│';
            foreach (array_values($row) as $i => $cell) {
                $line .= ' ' . str_pad((string)$cell, $widths[$i]) . ' │';
            }
            echo $line . PHP_EOL;
        }

        echo $bottomBorder . PHP_EOL;
    }

    private static function colorize(string $text, string $color): string
    {
        return (self::COLORS[$color] ?? '') . $text . self::COLORS['reset'];
    }
}
