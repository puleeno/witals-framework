<?php

declare(strict_types=1);

namespace Witals\Framework\Console\Commands;

use Witals\Framework\Console\Command;

abstract class MakeCommand extends Command
{
    protected string $type = '';

    abstract protected function getStub(): string;

    abstract protected function getPath(string $name): string;

    abstract protected function getNamespace(string $name): string;

    public function handle(array $args): int
    {
        $name = $args[0] ?? '';

        if (empty($name)) {
            $this->error("Usage: php witals {$this->name} <name>");
            return 1;
        }

        $path = $this->getPath($name);

        if (file_exists($path)) {
            $this->error("{$this->type} already exists at {$path}");
            return 1;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $stub = $this->getStub();
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ name }}'],
            [$this->getNamespace($name), $this->getClassName($name), $name],
            $stub
        );

        file_put_contents($path, $stub);
        $this->info("{$this->type} created successfully: {$path}");

        return 0;
    }

    protected function getClassName(string $name): string
    {
        return basename(str_replace('\\', '/', $name));
    }
}
