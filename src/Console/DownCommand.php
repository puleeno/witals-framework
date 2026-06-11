<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

class DownCommand extends Command
{
    protected string $name = 'down';
    protected string $description = 'Put the application into maintenance mode';

    protected array $options = [
        '--retry' => 'The number of seconds after which the request may be retried',
        '--message' => 'The message to display when the application is in maintenance mode',
        '--status' => 'The HTTP status code to return (default: 503)',
        '--allow' => 'IP address or CIDR to allow through maintenance mode (may be repeated)',
    ];

    public function handle(array $args): int
    {
        $options = $this->parseOptions($args);

        $data = [
            'time' => time(),
            'message' => $options['message'] ?? 'Application is in maintenance mode.',
            'retry' => isset($options['retry']) ? (int) $options['retry'] : null,
            'status' => (int) ($options['status'] ?? 503),
            'allowed' => $this->parseAllowed($options),
        ];

        $file = $this->app->basePath('storage/framework/down');
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($file, serialize($data));

        $this->info('Application is now in maintenance mode.');

        return 0;
    }

    protected function parseAllowed(array $options): array
    {
        $allowed = [];

        if (isset($options['allow'])) {
            $values = is_array($options['allow']) ? $options['allow'] : [$options['allow']];
            foreach ($values as $value) {
                $allowed[] = $value;
            }
        }

        return $allowed;
    }
}
