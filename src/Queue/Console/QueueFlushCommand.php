<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Console;

use Witals\Framework\Console\Command;
use Witals\Framework\Queue\QueueManager;

class QueueFlushCommand extends Command
{
    protected string $name = 'queue:flush';
    protected string $description = 'Delete all failed jobs';

    public function handle(array $args): int
    {
        /** @var QueueManager $manager */
        $manager = $this->app->make(QueueManager::class);
        $provider = $manager->getFailedJobProvider();

        if ($provider === null) {
            $this->warn('No failed job provider configured.');
            return 1;
        }

        $count = $provider->flush();
        $this->info(sprintf('Deleted %d failed job(s).', $count));

        return 0;
    }
}
