<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

use App\Foundation\Application;

class ConfigCacheCommand extends Command
{
    protected string $name = 'config:cache';
    protected string $description = 'Create a cache file for faster configuration loading';

    public function handle(array $args): int
    {
        /** @var Application $app */
        $app = $this->app;

        $cachePath = $app->basePath('bootstrap/cache/config.php');

        if (!is_dir(dirname($cachePath))) {
            mkdir(dirname($cachePath), 0755, true);
        }

        $repo = $app->getConfigRepository();
        $all = [];

        foreach ($repo->getPaths() as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '/*.php') ?: [];
            foreach ($files as $file) {
                $key = basename($file, '.php');
                $config = require $file;
                if (is_array($config)) {
                    $all[$key] = $config;
                }
            }

            $jsonFiles = glob($path . '/*.json') ?: [];
            foreach ($jsonFiles as $file) {
                $key = basename($file, '.json');
                $content = file_get_contents($file);
                if ($content !== false) {
                    $config = json_decode($content, true);
                    if (is_array($config)) {
                        $all[$key] = $config;
                    }
                }
            }
        }

        $content = '<?php return ' . var_export($all, true) . ';' . PHP_EOL;

        if (file_put_contents($cachePath, $content) === false) {
            $this->error('Failed to write config cache file.');
            return 1;
        }

        $this->info(sprintf('Configuration cached successfully (%d files, %d KB).',
            count($all),
            round(filesize($cachePath) / 1024)
        ));

        return 0;
    }
}
