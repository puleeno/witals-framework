<?php

declare(strict_types=1);

namespace Witals\Framework\Console\Commands;

use Witals\Framework\Module\ModuleManifest;

class MakeModuleCommand extends MakeCommand
{
    protected string $name = 'make:module';
    protected string $description = 'Create a new module with manifest.json structure';
    protected string $type = 'Module';
    protected array $arguments = ['name' => 'The name of the module (e.g., Blog)'];
    protected array $options = [
        '--presto' => 'Create in PrestoWorld modules (framework/presto/modules)',
        '--witals' => 'Create in Witals modules (framework/witals/modules) [default]',
    ];

    protected bool $isPresto = false;

    public function handle(array $args): int
    {
        global $argv;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--presto')) {
                $this->isPresto = true;
            }
        }

        $name = $args[0] ?? '';
        if (empty($name)) {
            $this->error("Usage: php witals {$this->name} <name> [--presto]");
            return 1;
        }

        $baseDir = $this->getBaseDir();
        $moduleDir = $baseDir . '/' . $name;
        $namespace = $this->getNamespace($name);

        if (is_dir($moduleDir)) {
            $this->error("Module '{$name}' already exists at {$moduleDir}");
            return 1;
        }

        mkdir($moduleDir, 0775, true);
        mkdir($moduleDir . '/src', 0775, true);

        // Generate manifest.json
        $manifest = ModuleManifest::generateStub($name, $namespace, "{$name} module");
        file_put_contents($moduleDir . '/manifest.json', $manifest);

        // Generate Module.php entry
        $entryStub = $this->getEntryStub($namespace, $name);
        file_put_contents($moduleDir . '/Module.php', $entryStub);

        // Generate a sample service provider
        $providerStub = $this->getProviderStub($namespace, $name);
        file_put_contents($moduleDir . '/src/ServiceProvider.php', $providerStub);

        $this->info("Module '{$name}' created successfully at {$moduleDir}");
        $this->line("  ├── manifest.json");
        $this->line("  ├── Module.php");
        $this->line("  └── src/ServiceProvider.php");

        return 0;
    }

    protected function getStub(): string
    {
        return '';
    }

    protected function getPath(string $name): string
    {
        return $this->getBaseDir() . '/' . $name;
    }

    protected function getNamespace(string $name): string
    {
        if ($this->isPresto) {
            return "PrestoWorld\\Modules\\{$name}";
        }
        return "Modules\\{$name}";
    }

    protected function getBaseDir(): string
    {
        if ($this->isPresto) {
            return $this->app->basePath() . '/framework/presto/modules';
        }
        return $this->app->basePath() . '/framework/witals/modules';
    }

    protected function getEntryStub(string $namespace, string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Witals\Framework\Module\Module as WitalsModule;

class Module extends WitalsModule
{
    public function register(): void
    {
        // \$this->app->singleton(YourService::class, fn() => new YourService());
    }

    public function boot(): void
    {
        // Boot logic here
    }
}
PHP;
    }

    protected function getProviderStub(string $namespace, string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Witals\Framework\Support\ServiceProvider;

class ServiceProvider extends \Witals\Framework\Support\ServiceProvider
{
    public function register(): void
    {
        // Bind services here
    }
}
PHP;
    }
}
