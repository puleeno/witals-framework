<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

use App\Foundation\Application;

class ConfigClearCommand extends Command
{
    protected string $name = 'config:clear';
    protected string $description = 'Remove the configuration cache file';

    public function handle(array $args): int
    {
        /** @var Application $app */
        $app = $this->app;

        $cachePath = $app->basePath('bootstrap/cache/config.php');

        if (file_exists($cachePath)) {
            unlink($cachePath);
            $this->info('Configuration cache cleared.');
        } else {
            $this->comment('No configuration cache found.');
        }

        return 0;
    }
}
