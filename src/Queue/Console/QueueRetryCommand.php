<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Console;

use Witals\Framework\Console\Command;
use Witals\Framework\Queue\QueueManager;

class QueueRetryCommand extends Command
{
    protected string $name = 'queue:retry';
    protected string $description = 'Retry a failed job by ID';

    protected array $arguments = [
        'id' => 'The failed job ID or "all" to retry all failed jobs',
    ];

    public function handle(array $args): int
    {
        $id = $args[0] ?? null;

        if ($id === null) {
            $this->error('Please provide a failed job ID or "all".');
            return 1;
        }

        /** @var QueueManager $manager */
        $manager = $this->app->make(QueueManager::class);
        $provider = $manager->getFailedJobProvider();

        if ($provider === null) {
            $this->warn('No failed job provider configured.');
            return 1;
        }

        if ($id === 'all') {
            $jobs = $provider->all();

            if ($jobs === []) {
                $this->info('No failed jobs to retry.');
                return 0;
            }

            $count = 0;
            foreach ($jobs as $job) {
                $job = (array) $job;
                $this->retryJob($manager, $provider, $job);
                $count++;
            }

            $this->info(sprintf('Retried %d failed job(s).', $count));
        } else {
            $job = $provider->find($id);

            if ($job === null) {
                $this->error(sprintf('No failed job found with ID "%s".', $id));
                return 1;
            }

            $this->retryJob($manager, $provider, $job);
            $this->info(sprintf('Retried failed job [%s].', $id));
        }

        return 0;
    }

    protected function retryJob(QueueManager $manager, $provider, array $job): void
    {
        $manager->connection($job['connection'])->pushRaw(
            $job['payload'],
            $job['queue'],
        );

        $provider->forget((string) $job['id']);
    }
}
