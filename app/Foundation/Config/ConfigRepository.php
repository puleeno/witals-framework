<?php

declare(strict_types=1);

namespace App\Foundation\Config;

use RuntimeException;

class ConfigRepository
{
    private array $paths;
    private array $loaded = [];

    public function __construct(string|array $paths = ['config'])
    {
        $this->paths = is_array($paths) ? $paths : [$paths];
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    public function setPaths(string|array $paths): void
    {
        $this->paths = is_array($paths) ? $paths : [$paths];
        $this->loaded = [];
    }

    public function addPath(string $path): void
    {
        $this->paths[] = $path;
        $this->loaded = [];
    }

    public function has(string $file): bool
    {
        if (isset($this->loaded[$file])) {
            return true;
        }

        foreach ($this->paths as $path) {
            if ($this->fileExists($path, $file)) {
                return true;
            }
        }

        return false;
    }

    public function load(string $file, mixed $default = null): mixed
    {
        if (isset($this->loaded[$file])) {
            return $this->loaded[$file];
        }

        foreach ($this->paths as $path) {
            $config = $this->loadFile($path, $file);
            if ($config !== null) {
                $this->loaded[$file] = $config;
                return $config;
            }
        }

        return $default;
    }

    public function set(string $file, array $config): void
    {
        $this->loaded[$file] = $config;
    }

    public function clearCache(): void
    {
        $this->loaded = [];
    }

    private function fileExists(string $path, string $file): bool
    {
        return file_exists($path . '/' . $file . '.php')
            || file_exists($path . '/' . $file . '.json');
    }

    private function loadFile(string $path, string $file): ?array
    {
        $phpPath = $path . '/' . $file . '.php';
        if (file_exists($phpPath)) {
            $result = require $phpPath;
            return is_array($result) ? $result : null;
        }

        $jsonPath = $path . '/' . $file . '.json';
        if (file_exists($jsonPath)) {
            $content = file_get_contents($jsonPath);
            if ($content === false) {
                throw new RuntimeException("Unable to read config file: {$jsonPath}");
            }
            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("Invalid JSON in config file {$jsonPath}: " . json_last_error_msg());
            }
            return $decoded;
        }

        return null;
    }
}
