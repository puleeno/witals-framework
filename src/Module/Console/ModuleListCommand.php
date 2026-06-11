<?php

declare(strict_types=1);

namespace Witals\Framework\Module\Console;

use Witals\Framework\Console\Command;

class ModuleListCommand extends Command
{
    protected string $name = 'module:list';
    protected string $description = 'List all discovered modules';

    public function handle(array $args): int
    {
        $manager = $this->app->make(\Witals\Framework\Module\ModuleManager::class);
        $manager->discover();

        $modules = $manager->all();

        if ($modules === []) {
            $this->comment('No modules found in "modules/" directory.');
            return 0;
        }

        $this->info(sprintf('%-25s %-10s %-8s %-8s %s', 'Name', 'Version', 'Enabled', 'Loaded', 'Path'));
        $this->line(str_repeat('-', 100));

        foreach ($modules as $name => $meta) {
            if (is_array($meta)) {
                $version = $meta['version'] ?? '1.0.0';
                $enabled = ($meta['enabled'] ?? false) ? 'Yes' : 'No';
                $loaded = 'No';
                $path = $meta['_path'] ?? '';
            } else {
                $version = $meta->getVersion();
                $enabled = $meta->isEnabled() ? 'Yes' : 'No';
                $loaded = $manager->isLoaded($name) ? 'Yes' : 'No';
                $path = $meta->getPath();
            }

            printf("%-25s %-10s %-8s %-8s %s\n", $name, $version, $enabled, $loaded, $path);
        }

        return 0;
    }
}
