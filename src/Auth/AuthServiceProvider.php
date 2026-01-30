<?php

declare(strict_types=1);

namespace Witals\Framework\Auth;

use Witals\Framework\Application;
use Witals\Framework\Contracts\Auth\TokenStorageInterface;
use Witals\Framework\Contracts\Auth\HttpTransportInterface;
use Witals\Framework\Auth\TokenStorage\ArrayTokenStorage;
use Witals\Framework\Auth\Transport\CookieTransport;

class AuthServiceProvider
{
    public function __construct(protected Application $app)
    {
    }

    public function boot(): void
    {
    }

    public function dependencies(): array
    {
        return [];
    }

    public function shouldLoad(): bool
    {
        return true;
    }

    public function register(): void
    {
        // 1. Token Storage Registration (Driver-based)
        if (!$this->app->has(TokenStorageInterface::class)) {
            $this->app->singleton(TokenStorageInterface::class, function ($app) {
                // Get driver from config, default to 'database'
                $driver = env('AUTH_TOKEN_DRIVER', 'database');

                switch ($driver) {
                    case 'redis':
                        // Ensure Redis service is available or create a new connection
                        // This assumes the app container has a 'redis' binding or we construct it
                        if ($app->has('redis')) {
                            return new \Witals\Framework\Auth\TokenStorage\RedisTokenStorage($app->make('redis'));
                        }
                        // Fallback simple Redis connection if not bound
                        $redis = new \Redis();
                        $redis->connect(env('REDIS_HOST', '127.0.0.1'), (int)env('REDIS_PORT', 6379));
                        return new \Witals\Framework\Auth\TokenStorage\RedisTokenStorage($redis);

                    case 'file':
                        return new \Witals\Framework\Auth\TokenStorage\FileTokenStorage(
                            $app->basePath('storage/framework/tokens')
                        );

                    case 'paseto':
                        // Needs APP_KEY or separate PASETO_KEY
                        $key = env('PASETO_KEY', env('APP_KEY', 'base64:randomkEyChangeMeINPROD1234567890'));
                        // Strip base64: prefix if present
                        if (str_starts_with($key, 'base64:')) {
                            $key = base64_decode(substr($key, 7));
                        }
                        return new \Witals\Framework\Auth\TokenStorage\PasetoTokenStorage($key);

                    case 'database':
                    default:
                        return new \Witals\Framework\Auth\TokenStorage\DatabaseTokenStorage(
                            $app->make(\Cycle\Database\DatabaseProviderInterface::class)
                        );
                }
            });
        }

        // 2. Default Http Transport (Cookie)
        // Users can override this to use HeaderTransport or others
        if (!$this->app->has(HttpTransportInterface::class)) {
            $this->app->singleton(HttpTransportInterface::class, function () {
                return new CookieTransport('token');
            });
        }

        // 3. Auth Context (Scoped per request)
        if (!$this->app->has(\Witals\Framework\Contracts\Auth\AuthContextInterface::class)) {
            $this->app->singleton(\Witals\Framework\Contracts\Auth\AuthContextInterface::class, function ($app) {
                return new \Witals\Framework\Auth\AuthContext(
                    $app->has(\Witals\Framework\Contracts\Auth\ActorProviderInterface::class)
                        ? $app->make(\Witals\Framework\Contracts\Auth\ActorProviderInterface::class)
                        : null
                );
            });
        }
    }
}
