<?php

declare(strict_types=1);

namespace Witals\Framework\Console\Commands;

use Witals\Framework\Console\Command;
use Witals\Framework\Module\ModuleManager;

class CacheClearCommand extends Command
{
    protected string $name = 'cache:clear';
    protected string $description = 'Purge all framework caches (module discovery, theme.json, etc.)';
    protected array $options = [
        '--modules' => 'Clear only module discovery cache',
        '--theme'   => 'Clear only theme.json CSS cache',
    ];

    public function handle(array $args): int
    {
        $options = $this->parseOptions($args);
        $clearModules = isset($options['modules']) || empty(array_intersect_key($options, ['modules' => true, 'theme' => true]));
        $clearTheme = isset($options['theme']) || empty(array_intersect_key($options, ['modules' => true, 'theme' => true]));

        $cleared = 0;

        if ($clearModules) {
            $cleared += $this->clearModulesCache();
        }

        if ($clearTheme) {
            $cleared += $this->clearThemeCache();
        }

        if ($cleared > 0) {
            $this->info("Cleared {$cleared} cache file(s).");
        } else {
            $this->comment('No cache files found to clear.');
        }

        return 0;
    }

    protected function clearModulesCache(): int
    {
        $count = 0;

        if ($this->app->has(ModuleManager::class)) {
            $manager = $this->app->make(ModuleManager::class);
            $manager->clearDiscoveryCache();
            $this->info('  [modules] Discovery cache cleared.');
            $count++;
        }

        // Also clear any standalone cache files
        $paths = [
            $this->app->basePath('storage/framework/cache/modules-discovery.php'),
            $this->app->basePath('framework/witals/storage/framework/cache/modules-discovery.php'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
                $count++;
            }
        }

        return $count;
    }

    protected function clearThemeCache(): int
    {
        $count = 0;
        $dirs = [
            $this->app->basePath('framework/witals/storage/framework/cache'),
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (glob($dir . '/themejson_*.php') as $file) {
                unlink($file);
                $count++;
            }
        }

        if ($count > 0) {
            $this->info("  [theme] Cleared {$count} theme.json cache file(s).");
        }

        return $count;
    }
}
