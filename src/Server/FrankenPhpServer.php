<?php

declare(strict_types=1);

namespace Witals\Framework\Server;

use Witals\Framework\Application;
use Witals\Framework\Contracts\Server;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Http\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;

class FrankenPhpServer implements Server
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->app->setRuntime(RuntimeType::FRANKENPHP);
    }

    public function start(): void
    {
        if (!function_exists('frankenphp_handle_request')) {
            fwrite(STDERR, "FrankenPHP is not available. Run with FrankenPHP binary.\n");
            exit(1);
        }

        $this->app->boot();

        $factory = new Psr17Factory();

        while (frankenphp_handle_request(function (ServerRequestInterface $psr7Request) use ($factory) {
            $request = Request::createFromPsr7($psr7Request);

            $response = $this->app->handle($request);

            $this->app->afterRequest($request, $response);

            return $response->toPsr7($factory);
        })) {
            // Loop continues until FrankenPHP signals shutdown
        }
    }

    public function isStateful(): bool
    {
        return true;
    }
}
