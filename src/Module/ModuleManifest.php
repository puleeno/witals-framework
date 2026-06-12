<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

class ModuleManifest
{
    protected array $data;
    protected string $path;

    protected static array $requiredFields = ['name', 'version'];

    protected static array $defaults = [
        'type' => 'support',
        'priority' => 50,
        'enabled' => true,
        'description' => '',
        'namespace' => '',
        'entry' => 'Module.php',
        'providers' => [],
        'routes' => [],
        'route_prefix' => '',
        'requires' => ['php' => '^8.1'],
        'dependencies' => [],
        'provides' => [],
        'autoload' => [],
        'functions' => [],
        'keywords' => [],
    ];

    public function __construct(string $modulePath)
    {
        $this->path = $modulePath;
        $this->data = $this->load($modulePath);
    }

    public static function exists(string $modulePath): bool
    {
        return file_exists($modulePath . '/manifest.json');
    }

    public static function find(string $directory): array
    {
        $modules = [];

        if (!is_dir($directory)) {
            return $modules;
        }

        foreach (scandir($directory) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $modulePath = $directory . '/' . $entry;

            if (!is_dir($modulePath)) {
                continue;
            }

            $manifest = new self($modulePath);

            if ($manifest->valid()) {
                $modules[$manifest->name()] = $manifest;
            }
        }

        return $modules;
    }

    protected function load(string $modulePath): array
    {
        $paths = [
            $modulePath . '/manifest.json',
            $modulePath . '/module.json',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $data = json_decode($content, true);

                if (is_array($data)) {
                    return $data;
                }
            }
        }

        return [];
    }

    public function valid(): bool
    {
        foreach (self::$requiredFields as $field) {
            if (!isset($this->data[$field]) || (is_string($this->data[$field]) && $this->data[$field] === '')) {
                return false;
            }
        }

        return true;
    }

    public function validate(): array
    {
        $errors = [];

        foreach (self::$requiredFields as $field) {
            if (!isset($this->data[$field]) || (is_string($this->data[$field]) && $this->data[$field] === '')) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        if (!isset($this->data['type'])) {
            $errors[] = "Missing type field (supported: support, route)";
        } elseif (!in_array($this->data['type'], ['support', 'route', 'theme'], true)) {
            $errors[] = "Invalid type: {$this->data['type']}. Must be: support, route, theme";
        }

        return $errors;
    }

    public function name(): string
    {
        return $this->data['name'] ?? 'unknown';
    }

    public function version(): string
    {
        return $this->data['version'] ?? '1.0.0';
    }

    public function description(): string
    {
        return $this->data['description'] ?? '';
    }

    public function type(): string
    {
        return $this->data['type'] ?? 'support';
    }

    public function priority(): int
    {
        return (int) ($this->data['priority'] ?? 50);
    }

    public function enabled(): bool
    {
        return $this->data['enabled'] ?? true;
    }

    public function namespace(): string
    {
        return $this->data['namespace'] ?? '';
    }

    public function entry(): string
    {
        return $this->data['entry'] ?? 'Module.php';
    }

    public function entryClass(): ?string
    {
        $ns = $this->namespace();
        $entry = $this->entry();

        if ($ns === '' || $entry === '') {
            return null;
        }

        $className = pathinfo($entry, PATHINFO_FILENAME);

        return $ns . '\\' . $className;
    }

    public function providers(): array
    {
        return $this->data['providers'] ?? [];
    }

    public function routes(): array
    {
        return $this->data['routes'] ?? [];
    }

    public function routePrefix(): string
    {
        return $this->data['route_prefix'] ?? '';
    }

    public function dependencies(): array
    {
        return $this->data['dependencies'] ?? [];
    }

    public function provides(): array
    {
        return $this->data['provides'] ?? [];
    }

    public function autoload(): array
    {
        return $this->data['autoload'] ?? [];
    }

    public function functions(): array
    {
        return $this->data['functions'] ?? [];
    }

    public function requires(): array
    {
        return $this->data['requires'] ?? ['php' => '^8.1'];
    }

    public function keywords(): array
    {
        return $this->data['keywords'] ?? [];
    }

    public function path(): string
    {
        return $this->path;
    }

    public function toArray(): array
    {
        return array_merge(self::$defaults, $this->data, [
            '_type' => $this->type(),
            '_path' => $this->path,
        ]);
    }

    public static function generateStub(string $name, string $namespace, string $description = '', string $type = 'support'): string
    {
        $stub = [
            'name' => strtolower($name),
            'version' => '1.0.0',
            'description' => $description ?: "{$name} module",
            'type' => $type,
            'priority' => 50,
            'enabled' => true,
            'namespace' => $namespace,
            'entry' => 'Module.php',
            'requires' => [
                'php' => '^8.1',
            ],
            'providers' => [],
            'autoload' => [
                'psr-4' => [
                    $namespace . '\\' => '.',
                ],
            ],
        ];

        return json_encode($stub, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
