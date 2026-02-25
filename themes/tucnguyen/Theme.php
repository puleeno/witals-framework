<?php

namespace Themes\Tucnguyen;

use PrestoWorld\Bridge\WordPress\Contracts\NativeComponentInterface;
use Witals\Framework\Http\Response;

/**
 * TucNguyen Native Theme
 * Optimized for RoadRunner
 */
class Theme implements NativeComponentInterface
{
    public function boot(): void
    {
        // Register native hooks (No Sandbox needed)
        add_filter('presto_page_title', [$this, 'modifyTitle']);
        
        // Configure Assets
        $assets = app(\Witals\Framework\Support\AssetManager::class);
        $assets->setContext('frontend'); // This sets mode to 'internal' (inline)
        
        // Enqueue frontend CSS (will be inlined in the view)
        $assets->enqueueCss('frontend-core', 'css/frontend.css');
        
        // Native themes can use PSR-4 autoloading via /themes/tucnguyen/src
    }

    public function handle(string $action, array $params = []): Response
    {
        $data = array_merge([
            'title' => 'Welcome to TucNguyen Theme',
            'content' => 'High performance rendering via PrestoWorld + RoadRunner'
        ], $params);

        // Define the hierarchy based on the action/context (WordPress-style)
        $hierarchy = [$action];

        // If it's a specific template (e.g. page-sample), fall back to base (e.g. page)
        if (preg_match('/^(page|single|archive|category|tag|taxonomy|author)-/', $action, $matches)) {
            $hierarchy[] = $matches[1];
        }
        
        $hierarchy[] = 'index';

        $themeManager = app(\PrestoWorld\Theme\ThemeManager::class);

        foreach ($hierarchy as $view) {
            try {
                // Use ThemeManager to render which correctly handles paths
                $html = $themeManager->render($view, $data);
                
                // Update Debug Context to the actual view found
                if (app()->has('current_context') || true) {
                    app()->instance('current_context', $view);
                }
                
                return Response::html($html);
            } catch (\Throwable $e) {
                // Continue to next fallback in hierarchy
                continue;
            }
        }

        return Response::html("Native Theme Error: No suitable template found for action '{$action}'", 500);
    }

    public function modifyTitle(string $title): string
    {
        return $title . ' | PrestoNative';
    }
}
