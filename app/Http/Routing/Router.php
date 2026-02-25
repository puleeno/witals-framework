<?php

declare(strict_types=1);

namespace App\Http\Routing;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Psr\Log\LoggerInterface;

use App\Http\Routing\Contracts\RouterInterface;

class Router implements RouterInterface
{
    protected Application $app;
    protected \Psr\Log\LoggerInterface $logger;
    protected array $routes = [];
    protected ?\Closure $wordPressFallback = null;

    public function __construct(Application $app, \Psr\Log\LoggerInterface $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    public function get(string $path, $action): Route
    {
        return $this->addRoute('GET', $path, $action);
    }

    public function post(string $path, $action): Route
    {
        return $this->addRoute('POST', $path, $action);
    }

    public function put(string $path, $action): Route
    {
        return $this->addRoute('PUT', $path, $action);
    }

    public function delete(string $path, $action): Route
    {
        return $this->addRoute('DELETE', $path, $action);
    }

    protected function addRoute(string $method, string $path, $action): Route
    {
        $route = new Route($method, $path, $action);
        $this->routes[] = $route;
        $this->logger->debug("Router: Registered route {method} {path}", ['method' => $method, 'path' => $path]);
        return $route;
    }

    public function setWordPressFallback(callable $fallback): void
    {
        $this->wordPressFallback = $fallback;
    }

    public function dispatch(Request $request): mixed
    {
        $path = $request->path();
        error_log("Router: dispatching {$_SERVER['REQUEST_METHOD']} {$path}");
        
        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                $this->logger->info("Router: Match found for {method} {path}", [
                    'method' => $request->method(),
                    'path' => $path
                ]);
                return $this->runRoute($route, $request);
            }
        }

        $this->logger->info("Router: No match for {method} {path}", [
            'method' => $request->method(),
            'path' => $path
        ]);

        // If no native route matches, try WordPress rewrite rules
        if ($this->wordPressFallback) {
            return ($this->wordPressFallback)($request);
        }

        return Response::json(['error' => 'Not Found'], 404);
    }

    protected function runRoute(Route $route, Request $request): mixed
    {
        $action = $route->getAction();

        if ($action instanceof \Closure) {
            return $this->app->call($action, array_merge(['request' => $request], $route->getParameters()));
        }

        if (is_array($action)) {
            [$controller, $method] = $action;
            if (is_string($controller)) {
                $controller = $this->app->make($controller);
            }
            return $this->app->call([$controller, $method], array_merge(['request' => $request], $route->getParameters()));
        }

        return $action;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
