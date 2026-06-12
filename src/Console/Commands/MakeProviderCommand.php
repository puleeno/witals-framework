<?php

declare(strict_types=1);

namespace Witals\Framework\Console\Commands;

class MakeProviderCommand extends MakeCommand
{
    protected string $name = 'make:provider';
    protected string $description = 'Create a new service provider';
    protected string $type = 'Service Provider';
    protected array $arguments = ['name' => 'The name of the provider (e.g., BlogServiceProvider)'];
    protected array $options = ['--module' => 'Create provider for a specific module'];

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Witals\Framework\Support\ServiceProvider;

class {{ class }} extends ServiceProvider
{
    public function register(): void
    {
        // $this->app->singleton(YourService::class, fn($app) => new YourService());
    }

    public function boot(): void
    {
        // Boot logic after all services are registered
    }
}
PHP;
    }

    protected function getPath(string $name): string
    {
        global $argv;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--module=')) {
                $module = substr($arg, 9);
                return $this->app->basePath() . "/framework/presto/modules/{$module}/Providers/{$name}.php";
            }
        }
        return $this->app->basePath() . "/framework/presto/Foundation/Providers/{$name}.php";
    }

    protected function getNamespace(string $name): string
    {
        global $argv;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--module=')) {
                $module = substr($arg, 9);
                return "PrestoWorld\\Modules\\{$module}\\Providers";
            }
        }
        return 'PrestoWorld\\Foundation\\Providers';
    }
}
