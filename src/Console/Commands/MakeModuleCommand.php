<?php

declare(strict_types=1);

namespace Witals\Framework\Console\Commands;

class MakeModuleCommand extends MakeCommand
{
    protected string $name = 'make:module';
    protected string $description = 'Create a new Witals module';
    protected string $type = 'Module';
    protected array $arguments = ['name' => 'The name of the module (e.g., Blog)'];
    protected array $options = ['--presto' => 'Create in PrestoWorld modules directory instead of Witals'];

    public function handle(array $args): int
    {
        global $argv;
        $isPresto = false;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--presto')) {
                $isPresto = true;
            }
        }
        $this->isPresto = $isPresto;
        return parent::handle($args);
    }

    protected bool $isPresto = false;

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Witals\Framework\Module\Module as WitalsModule;

class Module extends WitalsModule
{
    public function register(): void
    {
        // $this->app->singleton(YourService::class, fn() => new YourService());
    }

    public function boot(): void
    {
        // Boot logic here
    }
}
PHP;
    }

    protected function getPath(string $name): string
    {
        if ($this->isPresto) {
            return $this->app->basePath() . "/framework/presto/modules/{$name}/Module.php";
        }
        return $this->app->basePath() . "/framework/witals/modules/{$name}/Module.php";
    }

    protected function getNamespace(string $name): string
    {
        if ($this->isPresto) {
            return "PrestoWorld\\Modules\\{$name}";
        }
        return "Modules\\{$name}";
    }
}
