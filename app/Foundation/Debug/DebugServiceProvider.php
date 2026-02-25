<?php

declare(strict_types=1);

namespace App\Foundation\Debug;

use App\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(DebugBar::class, function ($app) {
            return new DebugBar($app);
        });
    }

    public function boot(): void
    {
        // Debug bar initialization
        if (env('APP_DEBUG_BAR', false)) {
            $hooks = $this->app->make('hooks');
            $hooks->addFilter('presto.response_body', static function($html) {
                if (str_contains($html, '</body>')) {
                    $debugBar = app(DebugBar::class);
                    // Use str_replace at the very end to minimize overhead
                    return str_replace('</body>', $debugBar->render() . '</body>', $html);
                }
                return $html;
            }, 999); // Run very last
        }
    }
}
