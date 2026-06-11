<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Console;

use Witals\Framework\Console\Command;
use Witals\Framework\Queue\QueueManager;

class QueueForgetCommand extends Command
{
    protected string $name = 'queue:forget';
    protected string $description = 'Delete a single failed job by ID';

    protected array $arguments = [
        'id' => 'The failed job ID',
    ];

    public function handle(array $args): int
    {
        $id = $args[0] ?? null;

        if ($id === null) {
            $this->error('Please provide a failed job ID.');
            return 1;
        }

        /** @var QueueManager $manager */
        $manager = $this->app->make(QueueManager::class);
        $provider = $manager->getFailedJobProvider();

        if ($provider === null) {
            $this->warn('No failed job provider configured.');
            return 1;
        }

        if ($provider->forget($id)) {
            $this->info(sprintf('Deleted failed job [%s].', $id));
        } else {
            $this->error(sprintf('No failed job found with ID "%s".', $id));
            return 1;
        }

        return 0;
    }
}
