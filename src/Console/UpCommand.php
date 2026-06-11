<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

class UpCommand extends Command
{
    protected string $name = 'up';
    protected string $description = 'Bring the application out of maintenance mode';

    public function handle(array $args): int
    {
        $file = $this->app->basePath('storage/framework/down');

        if (!file_exists($file)) {
            $this->comment('Application is already up.');
            return 0;
        }

        unlink($file);

        $this->info('Application is now live.');

        return 0;
    }
}
