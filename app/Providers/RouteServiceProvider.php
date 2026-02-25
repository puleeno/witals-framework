<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;
use App\Http\Routing\RouterFactory;
use App\Http\Routing\Contracts\RouterInterface;

class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Strategy selected ONCE at boot via Factory — zero per-request overhead
        $this->app->singleton(RouterInterface::class, function ($app) {
            return RouterFactory::create(
                $app,
                $app->make(\Psr\Log\LoggerInterface::class)
            );
        });

        // Keep Router::class alias so existing code that type-hints Router still works
        $this->app->singleton(Router::class, function ($app) {
            return $app->make(RouterInterface::class);
        });
    }

    public function boot(): void
    {
        $router = $this->app->make(RouterInterface::class);

        // Set the smart fallback to WordPress if the bridge is enabled
        $router->setWordPressFallback(function ($request) {
            $wpDispatcherClass = \PrestoWorld\Bridge\WordPress\Routing\WordPressDispatcher::class;
            return $this->app->make($wpDispatcherClass)->dispatch($request);
        });

        // Load modern routes
        $this->loadRoutes($router);
    }

    protected function loadRoutes(RouterInterface $router): void
    {
        $routesFile = $this->app->basePath('routes/web.php');
        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }
}
