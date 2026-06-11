<?php

declare(strict_types=1);

namespace Witals\Framework\Module\Console;

use Witals\Framework\Console\Command;

class ModuleDiscoverCommand extends Command
{
    protected string $name = 'module:discover';
    protected string $description = 'Discover and cache module routes for performance';

    public function handle(array $args): void
    {
        $manager = $this->app->make(\Witals\Framework\Module\ModuleManager::class);

        $this->info('Discovering modules...');

        $manager->discover();

        $index = $manager->buildRouteIndex();

        $this->info(sprintf('Found %d modules with %d routes in total.',
            count($manager->all()),
            count($index),
        ));

        foreach ($index as $entry) {
            $method = str_pad($entry['method'], 6);
            $this->line("  {$method} [{$entry['module']}]");
        }
    }
}
