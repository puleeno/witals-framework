<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Console;

use Witals\Framework\Console\Command;
use Witals\Framework\Queue\QueueManager;

class QueueFailedCommand extends Command
{
    protected string $name = 'queue:failed';
    protected string $description = 'List all failed jobs';

    public function handle(array $args): int
    {
        /** @var QueueManager $manager */
        $manager = $this->app->make(QueueManager::class);
        $provider = $manager->getFailedJobProvider();

        if ($provider === null) {
            $this->warn('No failed job provider configured.');
            return 1;
        }

        $jobs = $provider->all();

        if ($jobs === []) {
            $this->info('No failed jobs found.');
            return 0;
        }

        $this->info(sprintf('Found %d failed job(s):', count($jobs)));
        $this->line('');

        foreach ($jobs as $job) {
            $job = (array) $job;
            $payload = unserialize($job['payload']);
            $displayName = $payload['displayName'] ?? 'Unknown';
            $exception = $job['exception'] ?? '';
            $firstLine = explode("\n", $exception)[0];

            $this->line(sprintf('  [%s] %s', $job['id'], $displayName));
            $this->line(sprintf('         Connection: %s | Queue: %s', $job['connection'], $job['queue']));
            $this->line(sprintf('         Failed at: %s', $job['failed_at']));
            $this->line(sprintf('         Exception: %s', $firstLine));
            $this->line('');
        }

        return 0;
    }
}
