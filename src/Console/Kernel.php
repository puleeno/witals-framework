<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

use Witals\Framework\Application;
use Throwable;

class Kernel
{
    protected Application $app;
    protected array $commands = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(string $commandClass): void
    {
        $this->commands[] = $commandClass;
    }

    public function handle(array $argv): int
    {
        $commandName = $argv[1] ?? 'help';
        $args = array_slice($argv, 2);

        if ($commandName === 'help') {
            return $this->displayHelp();
        }

        foreach ($this->commands as $commandClass) {
            /** @var Command $command */
            $command = new $commandClass($this->app);
            if ($command->getName() === $commandName) {
                try {
                    return $command->handle($args);
                } catch (Throwable $e) {
                    echo "\033[31mError: {$e->getMessage()}\033[0m" . PHP_EOL;
                    echo "File: {$e->getFile()}:{$e->getLine()}" . PHP_EOL;
                    return 1;
                }
            }
        }

        echo "\033[31mUnknown command: {$commandName}\033[0m" . PHP_EOL;
        return $this->displayHelp();
    }

    protected function displayHelp(): int
    {
        echo "Witals Framework CLI" . PHP_EOL . PHP_EOL;
        echo "Usage:" . PHP_EOL;
        echo "  php witals <command> [options]" . PHP_EOL . PHP_EOL;
        echo "Available commands:" . PHP_EOL;

        foreach ($this->commands as $commandClass) {
            /** @var Command $command */
            $command = new $commandClass($this->app);
            printf("  %-20s %s\n", $command->getName(), $command->getDescription());
        }

        printf("  %-20s %s\n", 'help', 'Display this help message');
        
        return 0;
    }
}
