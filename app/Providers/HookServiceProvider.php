<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceProvider;
use PrestoWorld\Hooks\HookManager;

class HookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Witals\Framework\Support\EnvironmentDetector::class, function ($app) {
            return new \Witals\Framework\Support\EnvironmentDetector($app);
        });

        // 1. Determine Registry Implementation (Strategy Pattern via Config)
        $this->app->singleton(\PrestoWorld\Contracts\Hooks\Registries\HookRegistryInterface::class, function ($app) {
            // Auto-detect if not set
            $driver = env('HOOK_REGISTRY_DRIVER', 'memory'); 

            switch ($driver) {
                case 'redis':
                    if ($app->has('redis')) {
                        $redis = $app->make('redis');
                    } else {
                        $redis = new \Redis();
                        $redis->connect(env('REDIS_HOST', '127.0.0.1'), (int)env('REDIS_PORT', 6379));
                        if (env('REDIS_PASSWORD')) {
                            $redis->auth(env('REDIS_PASSWORD'));
                        }
                    }
                    return new \PrestoWorld\Hooks\Registries\RedisRegistry($redis);

                case 'mongodb':
                    $uri = env('MONGODB_URI', 'mongodb://localhost:27017');
                    $db = env('MONGODB_DATABASE', 'presto_core');
                    return new \PrestoWorld\Hooks\Registries\MongoRegistry($uri, $db);

                case 'sqlite':
                     $dbPath = path_join($app->basePath(), 'storage/framework/hooks.sqlite');
                     if (!is_dir(dirname($dbPath))) mkdir(dirname($dbPath), 0755, true);
                     return new \PrestoWorld\Hooks\Registries\SQLiteRegistry($dbPath);
                
                case 'apcu':
                    return new \PrestoWorld\Hooks\Registries\APCuRegistry();

                case 'swoole':
                    return new \PrestoWorld\Hooks\Registries\SwooleTableRegistry();
                
                case 'memory':
                default:
                    return new \PrestoWorld\Hooks\Registries\ArrayRegistry();
            }
        });


        // 2. Determine Dispatcher Implementation
        $this->app->singleton(\PrestoWorld\Contracts\Hooks\Dispatchers\ActionDispatcherInterface::class, function ($app) {
            $detector = $app->make(\Witals\Framework\Support\EnvironmentDetector::class);

            if ($detector->isModern() && $app->has('swoole.server')) {
                return new \PrestoWorld\Hooks\Dispatchers\SwooleTaskDispatcher($app, $app->make('swoole.server'));
            }

            return new \PrestoWorld\Hooks\Dispatchers\SyncDispatcher($app);
        });

        // 3. Determine State Driver Implementation
        $this->app->singleton(\PrestoWorld\Contracts\Hooks\StateDriverInterface::class, function ($app) {
            $detector = $app->make(\Witals\Framework\Support\EnvironmentDetector::class);

            if ($detector->isModern()) {
                return new \PrestoWorld\Hooks\State\SwooleStateDriver();
            }

            if ($detector->hasAPCu()) {
                return new \PrestoWorld\Hooks\State\APCuStateDriver();
            }

            return new \PrestoWorld\Hooks\State\ArrayStateDriver();
        });

        // 4. Register State Bridge
        $this->app->singleton(\PrestoWorld\Hooks\State\StateBridge::class, function ($app) {
            return new \PrestoWorld\Hooks\State\StateBridge(
                $app->make(\PrestoWorld\Contracts\Hooks\StateDriverInterface::class)
            );
        });

        // 5. Register Hook Manager
        $this->app->singleton(HookManager::class, function ($app) {
            return new HookManager(
                $app,
                $app->make(\PrestoWorld\Contracts\Hooks\Registries\HookRegistryInterface::class),
                $app->make(\PrestoWorld\Contracts\Hooks\Dispatchers\ActionDispatcherInterface::class)
            );
        });

        $this->app->alias(HookManager::class, 'hooks');
    }

    public function boot(): void
    {
        // Add cleanup hook to Application lifecycle
        $this->app->terminating(function () {
            if ($this->app->has(HookManager::class)) {
                $this->app->make(HookManager::class)->flushCache();
            }
        });

        // Register Core/Demo Hooks
        if ($this->app->has('hooks')) {
            $hooks = $this->app->make('hooks');

            // 1. Filter: Modify Title
            if (!$hooks->hasFilter('home_page_title')) {
                $hooks->addFilter('home_page_title', function($title) {
                    if (str_contains($title, 'Powered by PrestoWorld Hooks')) {
                        return $title;
                    }
                    return $title . ' - Powered by PrestoWorld Hooks';
                });
            }

            // 2. Filter: Inject content into Header
            if (!$hooks->hasFilter('home_page_content')) {
                $runtime = $this->app->isRoadRunner() ? 'RoadRunner' : (
                    $this->app->isOpenSwoole() ? 'OpenSwoole' : (
                    $this->app->isSwoole() ? 'Swoole' : 'Traditional Web Server'
                ));
                
                $hooks->addFilter('home_page_content', function($html) use ($runtime) {
                     if (str_contains($html, 'PrestoWorld Hooks Active')) {
                         return $html;
                     }
                     return str_replace('</body>', '<div style="background:linear-gradient(90deg, #ff00cc, #333399); color:white; padding:10px; text-align:center; position:fixed; top:0; left:0; width:100%; z-index:99999; font-weight:bold; box-shadow:0 2px 10px rgba(0,0,0,0.5);">⚡ PrestoWorld Hooks Active via ' . $runtime . '!</div></body>', $html);
                });
            }

            // 3. Admin Simulation: Register a widget and a menu page using WP helper style
            if (function_exists('wp_add_dashboard_widget') && !$hooks->hasAction('wp_dashboard_setup')) {
                wp_add_dashboard_widget('presto_welcome_widget', 'PrestoWorld Welcome', function() {
                    echo "<div class='premium-card' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px;'>
                            <h4>Modern Hybrid Rendering</h4>
                            <p>This content is rendered from a legacy-style <code>echo</code> within a WordPress callback, beautifully integrated into the PrestoWorld dashboard.</p>
                          </div>";
                });

                add_menu_page('Analytics Dashboard', 'Analytics', 'manage_options', 'presto-analytics', function() {
                    echo "<h1>Analytics View</h1><p>Rendering from callback...</p>";
                }, 'dashicons-chart-bar', 5);
            }
        }
    }
}
