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
        // 1. Default Token Storage (Array)
        // Users should override this in their AppServiceProvider for Database/Redis storage
        if (!$this->app->has(TokenStorageInterface::class)) {
            $this->app->singleton(TokenStorageInterface::class, ArrayTokenStorage::class);
        }

        // 2. Default Http Transport (Cookie)
        // Users can override this to use HeaderTransport or others
        if (!$this->app->has(HttpTransportInterface::class)) {
            $this->app->singleton(HttpTransportInterface::class, function () {
                return new CookieTransport('token');
            });
        }
    }
}
