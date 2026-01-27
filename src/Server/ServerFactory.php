<?php

declare(strict_types=1);

namespace Witals\Framework\Server;

use Witals\Framework\Application;
use Witals\Framework\Contracts\Server;
use Witals\Framework\Contracts\RuntimeType;

class ServerFactory
{
    /**
     * Create a server instance based on runtime type
     */
    public static function create(
        RuntimeType $runtime,
        Application $app,
        string $host = '0.0.0.0',
        int $port = 8080,
        array $options = []
    ): Server {
        return match ($runtime) {
            RuntimeType::ROADRUNNER => new RoadRunnerServer($app),
            RuntimeType::REACTPHP => new ReactPhpServer($app, $host, $port),
            RuntimeType::SWOOLE => new SwooleServer($app, $host, $port, $options),
            RuntimeType::OPENSWOOLE => new OpenSwooleServer($app, $host, $port, $options),
            RuntimeType::TRADITIONAL => new TraditionalServer($app),
        };
    }
}
