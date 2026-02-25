<?php

declare(strict_types=1);

namespace Witals\Framework\Seo;

use Witals\Framework\Support\ServiceProvider;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SeoManager::class, function ($app) {
            return new SeoManager();
        });
        
        $this->app->alias(SeoManager::class, 'seo');
    }

    public function boot(): void
    {
        // Add SEO to views if needed
        $this->app->view()->share('seo', $this->app->make(SeoManager::class));
    }
}
