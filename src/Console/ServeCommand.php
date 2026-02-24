<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Server\ServerFactory;

class ServeCommand extends Command
{
    protected string $name = 'serve';
    protected string $description = 'Start the application server';
    protected array $options = [
        '--host' => 'The host address to bind the server to (default: 0.0.0.0)',
        '--port' => 'The port number to listen on (default: 8080)',
        '--reactphp' => 'Force use of ReactPHP runtime',
        '--swoole' => 'Force use of Swoole runtime',
        '--openswoole' => 'Force use of OpenSwoole runtime',
        '--roadrunner' => 'Force use of RoadRunner runtime',
    ];

    public function handle(array $args): int
    {
        $options = $this->parseOptions($args);
        $host = $options['host'] ?? '0.0.0.0';
        $port = (int) ($options['port'] ?? 8080);

        // Detect or force runtime
        $runtime = $this->detectRuntime($options);

        // Avoid printing to STDOUT if RoadRunner is active, as it corrupts the protocol.
        // We output to STDERR instead so it still shows up in logs.
        $this->displayBanner($runtime, $host, $port);

        if ($runtime === RuntimeType::TRADITIONAL) {
            $this->error('Traditional runtime cannot be used with serve. Use public/index.php instead.');
            return 1;
        }

        // Create the server using the factory
        $server = ServerFactory::create($runtime, $this->app, $host, $port, [
            'worker_num' => (function_exists('swoole_cpu_num') ? swoole_cpu_num() : 4) * 2,
            'enable_coroutine' => true,
            'max_coroutine' => 100000,
            'max_request' => 10000,
        ]);

        $server->start();

        return 0;
    }

    protected function detectRuntime(array $options): RuntimeType
    {
        if (isset($_SERVER['RR_MODE']) || getenv('RR_MODE')) {
            return RuntimeType::ROADRUNNER;
        }

        if (isset($options['reactphp'])) {
            return RuntimeType::REACTPHP;
        }
        if (isset($options['swoole'])) {
            if (!extension_loaded('swoole')) {
                $this->exitWithError('Swoole extension is not installed. Run: pecl install swoole');
            }
            return RuntimeType::SWOOLE;
        }
        if (isset($options['openswoole'])) {
            if (!extension_loaded('openswoole')) {
                $this->exitWithError('OpenSwoole extension is not installed. Run: pecl install openswoole');
            }
            return RuntimeType::OPENSWOOLE;
        }
        if (isset($options['roadrunner'])) {
            return RuntimeType::ROADRUNNER;
        }

        $detected = RuntimeType::detect();
        if ($detected === RuntimeType::TRADITIONAL) {
            // ReactPHP can run standalone, so if it's installed, we can use it as default CLI server
            if (class_exists('React\Http\HttpServer')) {
                return RuntimeType::REACTPHP;
            }

            $this->exitWithError(
                "No suitable high-performance runtime detected for standalone execution.\n" .
                "To start the server, please do one of the following:\n" .
                "\n" .
                "1. If using RoadRunner:\n" .
                "   Run: rr serve\n" .
                "\n" .
                "2. If using standalone engines, install one of:\n" .
                "   - ReactPHP:    composer require react/http react/event-loop\n" .
                "   - Swoole:      pecl install swoole\n" .
                "   - OpenSwoole:  pecl install openswoole\n" .
                "\n" .
                "3. Or force a specific runtime with flags:\n" .
                "   --roadrunner, --reactphp, --swoole, --openswoole"
            );
        }

        return $detected;
    }

    protected function displayBanner(RuntimeType $runtime, string $host, int $port): void
    {
        $output = "╔════════════════════════════════════════════════════════════╗\n";
        $output .= "║           Witals Framework - Unified Worker               ║\n";
        $output .= "╚════════════════════════════════════════════════════════════╝\n";
        $output .= "\n";
        $output .= "Runtime:  {$runtime->value}\n";
        $output .= "Host:     {$host}\n";
        $output .= "Port:     {$port}\n";
        $output .= "URL:      http://{$host}:{$port}\n";
        $output .= "\n";
        $output .= str_repeat("─", 60) . "\n\n";

        if ($runtime === RuntimeType::ROADRUNNER) {
            file_put_contents('php://stderr', $output);
        } else {
            echo $output;
        }
    }

    protected function exitWithError(string $message): never
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║                         ERROR                              ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo $message . "\n\n";
        exit(1);
    }
}
