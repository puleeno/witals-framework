<?php

declare(strict_types=1);

namespace Witals\Framework\Auth\Middleware;

use Witals\Framework\Contracts\Auth\AuthContextInterface;
use Witals\Framework\Contracts\Auth\HttpTransportInterface;
use Witals\Framework\Contracts\Auth\TokenStorageInterface;
use Witals\Framework\Contracts\Container as ContainerContract;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\RequestHandler;
use Witals\Framework\Auth\AuthContext;

class AuthMiddleware
{
    public function __construct(
        protected ContainerContract $container,
        protected TokenStorageInterface $tokenStorage,
        protected HttpTransportInterface $httpTransport,
        protected ?AuthContextInterface $authContext = null // Optional pre-created context
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        // 1. Fetch Token ID from Transport
        $tokenID = $this->httpTransport->fetchToken($request);
        $token = null;

        // 2. Load Token from Storage
        if ($tokenID !== null) {
            $token = $this->tokenStorage->load($tokenID);
        }

        // 3. Create or reuse AuthContext
        // If we are in a container scope, we might want to resolve a fresh context
        // But here we will create one if not provided
        $authContext = $this->authContext ?? new AuthContext();

        // 4. Start Context
        if ($token !== null) {
            $authContext->start($token);
        }

        // 5. Run next handler within an IoC Scope
        // This ensures the AuthContext is available via dependency injection 
        // to any service requested within the processing of this request.
        
        // We assume the container has a 'runScope' method (compat with Witals Container)
        if (method_exists($this->container, 'runScope')) {
            return $this->container->runScope(
                [
                    AuthContextInterface::class => $authContext
                ],
                function () use ($next, $request) {
                    return $next($request);
                }
            );
        }

        // Fallback if no scoping is available (e.g. standard PHP-FPM without explicit scoping)
        // Check if we can bind it temporarily
        // Ideally we should NOT do this in RoadRunner/Swoole without scoping
        // But for safety, we'll try to use the passed context.
        return $next($request);
    }
}
