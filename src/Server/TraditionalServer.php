<?php

declare(strict_types=1);

namespace Witals\Framework\Server;

use Witals\Framework\Application;
use Witals\Framework\Contracts\Server;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Http\Request;

/**
 * Traditional PHP Server Adapter
 * Handles requests for traditional FPM/CGI environments
 */
class TraditionalServer implements Server
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        // Ensure runtime is set to traditional
        $this->app->setRuntime(RuntimeType::TRADITIONAL);
    }

    /**
     * Start the traditional request handling
     */
    public function start(): void
    {
        // 1. Boot the application
        $this->app->boot();

        // 2. Create request from globals
        $request = Request::createFromGlobals();

        // 3. Handle the request
        $response = $this->app->handle($request);

        // 4. Send the response
        $response->send();

        // 5. Terminate
        $this->app->terminate($request, $response);
    }

    /**
     * @inheritDoc
     */
    public function isStateful(): bool
    {
        return false;
    }
}
