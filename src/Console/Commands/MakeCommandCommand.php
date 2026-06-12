<?php

declare(strict_types=1);

namespace Witals\Framework\Console\Commands;

class MakeCommandCommand extends MakeCommand
{
    protected string $name = 'make:command';
    protected string $description = 'Create a new console command';
    protected string $type = 'Console Command';
    protected array $arguments = ['name' => 'The name of the command class (e.g., BlogExportCommand)'];

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Witals\Framework\Console\Command;

class {{ class }} extends Command
{
    protected string $name = 'command:name';
    protected string $description = 'Command description';
    protected array $arguments = [];
    protected array $options = [];

    public function handle(array $args): int
    {
        $this->info('Command executed successfully!');
        return 0;
    }
}
PHP;
    }

    protected function getPath(string $name): string
    {
        return $this->app->basePath() . "/app/Console/Commands/{$name}.php";
    }

    protected function getNamespace(string $name): string
    {
        return 'App\\Console\\Commands';
    }
}
