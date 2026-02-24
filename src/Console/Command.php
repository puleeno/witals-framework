<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

use Witals\Framework\Application;

abstract class Command
{
    protected Application $app;
    protected string $name = '';
    protected string $description = '';
    protected array $arguments = [];
    protected array $options = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    abstract public function handle(array $args): int;

    protected function info(string $message): void
    {
        $this->line("\033[32m{$message}\033[0m");
    }

    protected function error(string $message): void
    {
        $this->line("\033[31m{$message}\033[0m");
    }

    protected function warn(string $message): void
    {
        $this->line("\033[33m{$message}\033[0m");
    }

    protected function comment(string $message): void
    {
        $this->line("\033[36m{$message}\033[0m");
    }

    protected function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    protected function parseOptions(array $args): array
    {
        $options = [];
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $arg = substr($arg, 2);
                if (str_contains($arg, '=')) {
                    [$key, $value] = explode('=', $arg, 2);
                    $options[$key] = $value;
                } else {
                    $options[$arg] = true;
                }
            } elseif (str_starts_with($arg, '-')) {
                $arg = substr($arg, 1);
                $options[$arg] = true;
            }
        }
        return $options;
    }

    protected function hasOption(array $args, string $name, string $short = ''): bool
    {
        $options = $this->parseOptions($args);
        return isset($options[$name]) || ($short !== '' && isset($options[$short]));
    }

    protected function getOption(array $args, string $name, mixed $default = null): mixed
    {
        $options = $this->parseOptions($args);
        return $options[$name] ?? $default;
    }

    public function displayHelp(): void
    {
        echo "Description:" . PHP_EOL;
        echo "  " . $this->getDescription() . PHP_EOL . PHP_EOL;
        
        echo "Usage:" . PHP_EOL;
        echo "  php witals " . $this->getName() . " [options] [arguments]" . PHP_EOL . PHP_EOL;

        if (!empty($this->options)) {
            echo "Options:" . PHP_EOL;
            foreach ($this->options as $option => $desc) {
                printf("  %-20s %s\n", $option, $desc);
            }
        }
        printf("  %-20s %s\n", '-h, --help', 'Display this help message');

        if (!empty($this->arguments)) {
            echo PHP_EOL . "Arguments:" . PHP_EOL;
            foreach ($this->arguments as $arg => $desc) {
                printf("  %-20s %s\n", $arg, $desc);
            }
        }
    }
}
