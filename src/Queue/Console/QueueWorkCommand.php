<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Console;

use Witals\Framework\Console\Command;
use Witals\Framework\Queue\QueueManager;

class QueueWorkCommand extends Command
{
    protected string $name = 'queue:work';
    protected string $description = 'Process jobs from the queue';

    protected array $arguments = [
        'connection' => 'The name of the queue connection to work',
    ];

    protected array $options = [
        '--queue' => 'The queue to listen on',
        '--sleep' => 'Seconds to sleep when no job is available (default: 3)',
        '--max-tries' => 'Maximum number of attempts per job (default: 0 = unlimited)',
        '--backoff' => 'Seconds to wait before retrying a failed job (default: 0)',
        '--timeout' => 'The number of seconds a child process can run (default: 60)',
        '--memory' => 'The memory limit in megabytes (default: 128)',
        '--rest' => 'Seconds to wait before restarting the worker (default: 0)',
    ];

    public function handle(array $args): int
    {
        $connection = $args[0] ?? 'default';
        $options = $this->parseOptions($args);

        $this->info("Starting queue worker for connection: {$connection}");
        $this->info("  queue: " . ($options['queue'] ?? 'default'));
        $this->info("  sleep: " . ($options['sleep'] ?? 3));
        $this->info("  max_tries: " . ($options['max-tries'] ?? 0));
        $this->info("  memory: " . ($options['memory'] ?? 128) . "MB");

        /** @var QueueManager $manager */
        $manager = $this->app->make(QueueManager::class);
        $worker = $manager->getWorker();

        if ($this->app->has(\Psr\Log\LoggerInterface::class)) {
            $worker->setLogger($this->app->make(\Psr\Log\LoggerInterface::class));
        }

        $worker->daemon(
            connection: $connection,
            queue: $options['queue'] ?? 'default',
            options: [
                'sleep' => (int) ($options['sleep'] ?? 3),
                'max_tries' => (int) ($options['max-tries'] ?? 0),
                'backoff' => (int) ($options['backoff'] ?? 0),
                'timeout' => (int) ($options['timeout'] ?? 60),
                'memory' => (int) ($options['memory'] ?? 128),
                'rest' => (int) ($options['rest'] ?? 0),
            ],
        );

        return 0;
    }
}
