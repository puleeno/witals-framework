<?php

declare(strict_types=1);

namespace App\Foundation\Debug;

use App\Foundation\Application;
use PrestoWorld\Theme\ThemeManager;

/**
 * Class DebugBar
 * 
 * Optimized for performance in long-running environments (RoadRunner).
 * Uses a 'send-to-forget' philosophy: data is collected throughout the request,
 * and rendering is streamlined to minimize overhead.
 */
class DebugBar
{
    protected Application $app;
    protected float $startTime;
    protected array $queries = [];
    protected array $benchmarks = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->reset();
    }

    /**
     * Reset the debug state for a new request.
     */
    public function reset(): void
    {
        $this->startTime = microtime(true);
        $this->queries = [];
        $this->benchmarks = [];
    }

    /**
     * Start a performance timer.
     */
    public function startTimer(string $name): void
    {
        $this->benchmarks[$name] = microtime(true);
    }

    /**
     * End a performance timer and return duration.
     */
    public function endTimer(string $name): float
    {
        if (!isset($this->benchmarks[$name])) {
            return 0;
        }
        $duration = microtime(true) - $this->benchmarks[$name];
        $this->benchmarks[$name] = $duration;
        return $duration;
    }

    /**
     * Log a database query.
     */
    public function logQuery(string $sql, float $time, array $bindings = []): void
    {
        // Limit number of queries logged to prevent memory issues in debug mode
        if (count($this->queries) > 100) return;

        $this->queries[] = [
            'sql' => $sql,
            'time' => $time,
            'bindings' => $bindings
        ];
    }

    /**
     * Render the Debug Bar HTML.
     * 
     * Refined to show the exact 'Context' (Template) used by the Native Engine.
     */
    public function render(): string
    {
        $totalTime = (microtime(true) - $this->startTime) * 1000;
        $memory = memory_get_peak_usage(true) / 1024 / 1024;
        $queryCount = count($this->queries);
        $queryTime = array_sum(array_column($this->queries, 'time')) * 1000;

        // Theme and Engine Info
        $themeName = 'None';
        $themeEngine = 'None';
        $templateEngine = 'PHP';

        if ($this->app->has(ThemeManager::class)) {
            $themeManager = $this->app->make(ThemeManager::class);
            $activeTheme = $themeManager->getActiveTheme();
            if ($activeTheme) {
                $themeName = $activeTheme->getName();
                $engine = $activeTheme->getEngine();
                $themeEngine = ucfirst($activeTheme->getType());
                $templateEngine = $engine->getTemplateEngineName();
            }
        }

        // Context Detection (Determined by Native Theme Hierarchy)
        $context = 'index';
        if ($this->app->has('current_context')) {
            $context = (string)$this->app->make('current_context');
        } elseif (isset($GLOBALS['__presto_current_context'])) {
            $context = (string)$GLOBALS['__presto_current_context'];
        }

        // Environment
        $phpVersion = PHP_VERSION;
        $serverInfo = $this->app->isRoadRunner() ? 'RoadRunner' : (PHP_SAPI);

        $html = "
        <!-- PrestoWorld Debug Bar: Performance Optimized -->
        <style>
            #pw-debug-bar {
                position: fixed; bottom: 0; left: 0; right: 0; height: 32px;
                background: #0f172a; color: #f8fafc; font-family: 'Inter', system-ui, sans-serif;
                font-size: 11px; display: flex; align-items: center; padding: 0 15px;
                border-top: 1px solid #1e293b; z-index: 1000000; box-shadow: 0 -4px 6px rgba(0,0,0,0.3);
            }
            .pw-db-item { display: flex; align-items: center; margin-right: 18px; }
            .pw-db-label { color: #64748b; margin-right: 4px; }
            .pw-db-value { font-weight: 600; color: #818cf8; }
            .pw-db-highlight { color: #fbbf24; }
            .pw-db-separator { height: 14px; width: 1px; background: #334155; margin-right: 18px; }
            
            .pw-query-popover {
                display: none; position: absolute; bottom: 32px; left: 100px;
                background: #0f172a; border: 1px solid #334155; padding: 15px;
                width: 600px; max-height: 400px; overflow-y: auto; border-radius: 8px 8px 0 0;
                box-shadow: 0 -10px 25px rgba(0,0,0,0.5);
            }
            .pw-db-item:hover .pw-query-popover { display: block; }
            .pw-query-item { padding: 6px 0; border-bottom: 1px solid #1e293b; font-family: monospace; }
        </style>
        <div id='pw-debug-bar'>
            <div class='pw-db-item' title='Total Request Time'>
                <span style='margin-right:6px'>⚡</span>
                <span class='pw-db-value'>" . number_format($totalTime, 2) . "ms</span>
            </div>
            <div class='pw-db-item' title='Peak Memory Usage'>
                <span style='margin-right:6px'>🧠</span>
                <span class='pw-db-value'>" . number_format($memory, 2) . "MB</span>
            </div>
            <div class='pw-db-item' style='position: relative; cursor: pointer;'>
                <span style='margin-right:6px'>🗄️</span>
                <span class='pw-db-label'>SQL:</span>
                <span class='pw-db-value'>$queryCount</span>
                <div class='pw-query-popover'>
                    <div style='font-weight:bold; margin-bottom:10px; color:#fff'>SQL Queries (" . number_format($queryTime, 2) . "ms)</div>
                    " . array_reduce($this->queries, function($c, $q) {
                        return $c . "<div class='pw-query-item'>
                            <span style='color:#10b981'>(" . number_format($q['time']*1000, 2) . "ms)</span>
                            <code>" . htmlspecialchars($q['sql']) . "</code>
                        </div>";
                    }, '') . "
                </div>
            </div>

            <div class='pw-db-separator'></div>

            <div class='pw-db-item'>
                <span class='pw-db-label'>Theme:</span>
                <span class='pw-db-value pw-db-highlight'>$themeName</span>
            </div>
            <div class='pw-db-item'>
                <span class='pw-db-label'>Engine:</span>
                <span class='pw-db-value'>$themeEngine / $templateEngine</span>
            </div>

            <div class='pw-db-separator'></div>

            <div class='pw-db-item' title='Current Active Template'>
                <span class='pw-db-label'>Context:</span>
                <span class='pw-db-value' style='color:#ec4899'>$context</span>
            </div>

            <div class='pw-db-item' style='margin-left: auto'>
                <span class='pw-db-label'>PHP:</span>
                <span class='pw-db-value' style='color:#fff'>$phpVersion</span>
                <span class='pw-db-label' style='margin-left:10px'>Server:</span>
                <span class='pw-db-value' style='color:#fff'>$serverInfo</span>
                <span style='font-weight:bold; color:#fff; margin-left:20px'>PrestoWorld</span>
            </div>
        </div>";

        return $html;
    }
}
