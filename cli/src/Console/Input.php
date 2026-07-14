<?php

declare(strict_types=1);

namespace Sadi\Console;

class Input
{
    private string $command = '';
    private array $arguments = [];
    private array $options = [];

    public function __construct(array $argv)
    {
        // argv[0] = script, argv[1] = command, rest = args/options
        $tokens = array_slice($argv, 1);

        if (empty($tokens)) {
            return;
        }

        $this->command = array_shift($tokens);

        foreach ($tokens as $token) {
            if (str_starts_with($token, '--')) {
                // --option=value or --flag
                $parts = explode('=', substr($token, 2), 2);
                $this->options[$parts[0]] = $parts[1] ?? true;
            } elseif (str_starts_with($token, '-')) {
                // -f shorthand
                $this->options[substr($token, 1)] = true;
            } else {
                $this->arguments[] = $token;
            }
        }
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getArgument(int $index, string $default = ''): string
    {
        return $this->arguments[$index] ?? $default;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }
}
