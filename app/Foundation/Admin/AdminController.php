<?php

declare(strict_types=1);

namespace App\Foundation\Admin;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Base Admin Controller
 *
 * Provides common helpers for all module admin controllers:
 *   - HTML page scaffold (header, nav, footer)
 *   - Form field renderers (input, select, textarea, checkbox…)
 *   - Flash / notice rendering
 *   - JSON response helper
 *   - Redirect helper
 *
 * Every module admin controller extends this class.
 */
abstract class AdminController
{
    protected mixed $app;
    protected \Witals\Framework\Support\AssetManager $assets;

    public function __construct(mixed $app)
    {
        $this->app = $app;
        $this->assets = $app->make(\Witals\Framework\Support\AssetManager::class);
    }

    // =========================================================================
    // Page scaffold
    // =========================================================================

    /**
     * Wrap content in the shared admin page chrome.
     *
     * @param string $title    Page/section title
     * @param string $content  Body HTML
     * @param array  $options  ['new_url' => '/dashboard/x/create', 'breadcrumbs' => [...]]
     */
    protected function adminPage(string $title, string $content, array $options = []): string
    {
        $newUrl      = $options['new_url'] ?? '';
        $newLabel    = $options['new_label'] ?? 'Add New';
        $breadcrumbs = $options['breadcrumbs'] ?? [];

        $addBtn  = $newUrl
            ? "<a href=\"{$newUrl}\" class=\"presto-btn presto-btn-primary\">{$newLabel}</a>"
            : '';

        $breadcrumbHtml = '';
        if (!empty($breadcrumbs)) {
            $parts = [];
            foreach ($breadcrumbs as $label => $url) {
                $parts[] = $url ? "<a href=\"{$url}\">{$label}</a>" : "<span>{$label}</span>";
            }
            $breadcrumbHtml = '<nav class="presto-breadcrumbs">' . implode(' › ', $parts) . '</nav>';
        }

        // Configure assets for Admin (using advanced AssetManager)
        $this->assets->setContext('admin');
        
        // admin-dashboard depends on admin-core.css
        $this->assets->enqueueCss('admin-core', 'css/admin-core.css');
        $this->assets->enqueueCss('admin-dashboard', 'css/admin-dashboard.css', ['admin-core']);

        // JS assets
        $this->assets->enqueueJs('admin-core', 'js/admin-solid-core.js', [], ['defer' => true, 'type' => 'module']);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title} — DigitalCore Admin</title>
            {$this->assets->renderCss()}
            <style>{$this->adminCss()}</style>
        </head>
        <body class="presto-admin">
            <div class="presto-admin-layout">
                <aside class="presto-sidebar">
                    <div class="presto-sidebar-brand">
                        <svg width="24" height="24" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="32" height="32" rx="8" fill="#6366f1"/>
                            <path d="M12 10H20C21.1046 10 22 10.8954 22 12V20C22 21.1046 21.1046 22 20 22H12C10.8954 22 10 21.1046 10 20V12C10 10.8954 10.8954 10 12 10Z" stroke="white" stroke-width="2"/>
                        </svg>
                        Digital<span>Core.</span>
                    </div>
                    {$this->adminNav()}
                    <div class="sidebar-footer">
                        <div class="nav-user-profile">
                            <div class="avatar" style="background: linear-gradient(135deg, #6366f1, #a855f7);">AD</div>
                            <div class="info">
                                <span class="name">Alexander Dev</span>
                                <span class="role">Super Admin</span>
                            </div>
                            <button class="logout-btn">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>
                </aside>
                <main class="presto-main-wrapper">
                    <header class="presto-main-header">
                        <div class="header-breadcrumb">
                            {$breadcrumbHtml}
                        </div>
                        <div class="header-actions">
                            <div class="header-search">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <input type="text" placeholder="Tìm kiếm License key, tên khách hàng...">
                            </div>
                            <div class="header-notif">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span class="pulse"></span>
                            </div>
                            {$addBtn}
                        </div>
                    </header>
                    <div class="presto-content-area">
                        <h1 class="page-title">{$title}</h1>
                        {$content}
                        
                        <footer class="presto-admin-footer">
                            <div class="footer-left">
                                <strong>DigitalCore</strong> v2.4.0 — Premium Admin Experience
                            </div>
                            <div class="footer-right">
                                Created with &hearts; by DeepMind & Optilarity Team
                            </div>
                        </footer>
                    </div>
                </main>
            </div>
            {$this->assets->renderJs()}
            <script>{$this->adminJs()}</script>
        </body>
        </html>
        HTML;
    }

    protected function adminNav(): string
    {
        $groups = [
            ['label' => 'Bảng điều khiển', 'url' => '/dashboard', 'icon' => '📊'],
            'Kinh doanh' => [
                'icon' => '🛍️',
                'children' => [
                    ['label' => 'Đơn hàng',      'url' => '/dashboard/orders',     'icon' => '🛒'],
                    ['label' => 'Khách hàng',    'url' => '/dashboard/customers',  'icon' => '👥'],
                    ['label' => 'Hóa đơn',       'url' => '/dashboard/invoices',   'icon' => '📄'],
                    ['label' => 'Cộng tác viên', 'url' => '/dashboard/affiliates', 'icon' => '🤝'],
                ]
            ],
            'Sản phẩm Số' => [
                'icon' => '📦',
                'children' => [
                    ['label' => 'Gói Thành Viên', 'url' => '/dashboard/memberships', 'icon' => '💎'],
                    ['label' => 'Software Licenses', 'url' => '/dashboard/licenses', 'icon' => '💻'],
                    ['label' => 'Themes Manager', 'url' => '/dashboard/catalog?type=theme', 'icon' => '🎨'],
                    ['label' => 'Plugins Repository', 'url' => '/dashboard/catalog?type=plugin', 'icon' => '🔌'],
                    ['label' => 'Quản lý Dịch vụ', 'url' => '/dashboard/web-services', 'icon' => '🛠️'],
                ]
            ],
            'Dịch vụ Hạ tầng' => [
                'icon' => '☁️',
                'children' => [
                    ['label' => 'Quản lý Hosting',  'url' => '/dashboard/hosting',   'icon' => '🖥️'],
                    ['label' => 'Quản lý Tên miền', 'url' => '/dashboard/domains',   'icon' => '🌐'],
                    ['label' => 'Chứng chỉ SSL',    'url' => '/dashboard/infrastructure/ssl', 'icon' => '🔒'],
                    ['label' => 'Email Hosting',    'url' => '/dashboard/infrastructure/email', 'icon' => '📧'],
                ]
            ],
            'Hệ thống' => [
                'icon' => '⚙️',
                'children' => [
                    ['label' => 'API Keys (Updater)', 'url' => '/dashboard/profile', 'icon' => '⚡'],
                    ['label' => 'Webhooks',      'url' => '/dashboard/webhooks',   'icon' => '🪝'],
                ]
            ]
        ];

        $current = $_SERVER['REQUEST_URI'] ?? '';
        $html = '<div class="presto-nav-groups">';
        
        // Check if any submenu child is active
        $anySubmenuActive = false;
        foreach ($groups as $data) {
            if (isset($data['children'])) {
                foreach ($data['children'] as $child) {
                    if ($current === $child['url'] || str_starts_with($current, $child['url'] . '?')) {
                        $anySubmenuActive = true;
                        break 2;
                    }
                }
            }
        }

        foreach ($groups as $groupLabel => $data) {
            // Case 1: Simple Link (Indexed or has 'url')
            if (isset($data['url'])) {
                $active = ($current === $data['url'] || str_starts_with($current, $data['url'] . '?')) ? ' active' : '';
                $html .= "<a href=\"{$data['url']}\" class=\"presto-nav-item{$active}\">";
                $html .= "  <span class='nav-icon'>{$data['icon']}</span> <span class='nav-label'>{$data['label']}</span>";
                $html .= "</a>";
                continue;
            }

            // Case 2: Named Group with Children
            if (!isset($data['children']) || !is_array($data['children'])) {
                continue;
            }

            $hasActiveChild = false;
            foreach ($data['children'] as $child) {
                if ($current === $child['url'] || str_starts_with($current, $child['url'] . '?')) {
                    $hasActiveChild = true;
                    break;
                }
            }

            // Default to open 'Kinh doanh' if no other submenu is active
            $shouldOpen = $hasActiveChild || (!$anySubmenuActive && $groupLabel === 'Kinh doanh');
            $openClass = $shouldOpen ? ' is-open' : '';
            $activeParentClass = $hasActiveChild ? ' active-parent' : '';
            
            $html .= "<div class=\"presto-nav-group-wrapper{$openClass}\">";
            $html .= "  <div class=\"presto-nav-item has-children{$activeParentClass}\" data-toggle=\"submenu\">";
            $html .= "      <span class='nav-icon'>{$data['icon']}</span>";
            $html .= "      <span class='nav-label'>{$groupLabel}</span>";
            $html .= "      <span class='nav-chevron'><svg width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><path d='M6 9l6 6 6-6'/></svg></span>";
            $html .= "  </div>";
            $html .= "  <div class=\"presto-submenu\">";
            
            foreach ($data['children'] as $child) {
                $childActive = ($current === $child['url'] || str_starts_with($current, $child['url'] . '?')) ? ' active' : '';
                $html .= "      <a href=\"{$child['url']}\" class=\"presto-submenu-item{$childActive}\">";
                $html .= "          <span class='sub-icon'>{$child['icon']}</span> <span class='sub-label'>{$child['label']}</span>";
                $html .= "      </a>";
            }
            
            $html .= "  </div>";
            $html .= "</div>";
        }
        
        $html .= '</div>';
        return $html;
    }

    // =========================================================================
    // Notices
    // =========================================================================

    protected function notice(string $message, string $type = 'info'): string
    {
        $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
        $icon  = $icons[$type] ?? 'ℹ️';
        return "<div class=\"presto-notice presto-notice-{$type}\">{$icon} {$message}</div>";
    }

    // =========================================================================
    // Form field helpers
    // =========================================================================

    protected function formOpen(string $action, string $method = 'POST', string $id = ''): string
    {
        $idAttr = $id ? " id=\"{$id}\"" : '';
        // PUT/PATCH/DELETE need _method override for HTML forms
        $realMethod = strtoupper($method);
        $formMethod = in_array($realMethod, ['GET', 'POST'], true) ? $realMethod : 'POST';
        $methodField = !in_array($realMethod, ['GET', 'POST'], true)
            ? "<input type=\"hidden\" name=\"_method\" value=\"{$realMethod}\">"
            : '';
        return "<form action=\"{$action}\" method=\"{$formMethod}\"{$idAttr} class=\"presto-form\">{$methodField}";
    }

    protected function formClose(): string
    {
        return '</form>';
    }

    protected function fieldGroup(string $label, string $input, string $hint = ''): string
    {
        $hintHtml = $hint ? "<p class=\"presto-field-hint\">{$hint}</p>" : '';
        return <<<HTML
        <div class="presto-field-group">
            <label class="presto-field-label">{$label}</label>
            <div class="presto-field-control">{$input}{$hintHtml}</div>
        </div>
        HTML;
    }

    protected function input(
        string $name,
        string $type = 'text',
        mixed  $value = '',
        string $placeholder = '',
        bool   $required = false
    ): string {
        $req   = $required ? ' required' : '';
        $ph    = $placeholder ? " placeholder=\"{$placeholder}\"" : '';
        $val   = htmlspecialchars((string)$value, ENT_QUOTES);
        return "<input type=\"{$type}\" name=\"{$name}\" id=\"field-{$name}\" value=\"{$val}\" class=\"presto-input\" autocomplete=\"off\"{$ph}{$req}>";
    }

    protected function textarea(string $name, mixed $value = '', string $placeholder = '', int $rows = 4): string
    {
        $ph  = $placeholder ? " placeholder=\"{$placeholder}\"" : '';
        $val = htmlspecialchars((string)$value, ENT_QUOTES);
        return "<textarea name=\"{$name}\" id=\"field-{$name}\" rows=\"{$rows}\" class=\"presto-input presto-textarea\"{$ph}>{$val}</textarea>";
    }

    protected function select(string $name, array $options, mixed $selected = '', bool $searchable = false, string $placeholder = 'Search...'): string
    {
        if ($searchable) {
            $formattedOptions = [];
            foreach ($options as $val => $lbl) {
                $formattedOptions[] = ['value' => (string)$val, 'label' => (string)$lbl];
            }
            return $this->searchableSelect($name, $formattedOptions, $selected, $placeholder);
        }

        $html = "<select name=\"{$name}\" id=\"field-{$name}\" class=\"presto-select\">";
        foreach ($options as $val => $label) {
            $sel   = (string)$val === (string)$selected ? ' selected' : '';
            $html .= "<option value=\"{$val}\"{$sel}>{$label}</option>";
        }
        $html .= '</select>';
        return $html;
    }

    protected function searchableSelect(string $name, array $options, mixed $value = '', string $placeholder = 'Search...'): string
    {
        $jsonOptions = json_encode($options);
        return <<<HTML
        <div data-solid-component="ComboBox" data-config='{"name":"{$name}", "options":{$jsonOptions}, "value":"{$value}", "placeholder":"{$placeholder}"}'></div>
HTML;
    }

    protected function searchableFieldGroup(string $label, string $name, array $options, mixed $value = '', string $placeholder = 'Search...'): string
    {
        return <<<HTML
        <div class="presto-field-group">
            <label class="presto-field-label">{$label}</label>
            <div class="presto-field-control">{$this->searchableSelect($name, $options, $value, $placeholder)}</div>
        </div>
HTML;
    }

    protected function checkbox(string $name, bool $checked = false, string $label = ''): string
    {
        $chk  = $checked ? ' checked' : '';
        $html = "<label class=\"presto-checkbox-label\">"
              . "<input type=\"checkbox\" name=\"{$name}\" id=\"field-{$name}\" value=\"1\"{$chk} class=\"presto-checkbox\">"
              . " {$label}</label>";
        return $html;
    }

    protected function submitBar(string $label = 'Save', string $cancelUrl = ''): string
    {
        $cancel = $cancelUrl
            ? "<a href=\"{$cancelUrl}\" class=\"presto-btn presto-btn-ghost\">Cancel</a>"
            : '';
        return "<div class=\"presto-submit-bar\">{$cancel}<button type=\"submit\" class=\"presto-btn presto-btn-primary\">{$label}</button></div>";
    }

    /**
     * Render a full presto-card-wrapped form section.
     */
    protected function formCard(string $title, string $fieldsHtml): string
    {
        return <<<HTML
        <div class="presto-card">
            <div class="presto-card-header"><h2 class="presto-card-title">{$title}</h2></div>
            <div class="presto-card-body">{$fieldsHtml}</div>
        </div>
        HTML;
    }

    // =========================================================================
    // Response helpers
    // =========================================================================

    protected function htmlResponse(string $html, int $status = 200): Response
    {
        return Response::html($html, $status);
    }

    protected function jsonResponse(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return new Response($status, ['Location' => $url], '');
    }

    // =========================================================================
    // DB helpers
    // =========================================================================

    protected function db(): \Cycle\Database\DatabaseInterface
    {
        return $this->app->make(\Cycle\Database\DatabaseInterface::class);
    }

    // =========================================================================
    // Inline CSS + JS (shared admin DesignSystem)
    // =========================================================================

    protected function adminCss(): string
    {
        return <<<CSS
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        :root {
            --bg-deep: #06080c;
            --bg-side: #0b0e14;
            --bg-card: rgba(18, 22, 31, 0.85);
            --bg-card-hover: rgba(26, 31, 46, 0.95);
            --primary: #6366f1;
            --primary-700: #4f46e5;
            --primary-glow: rgba(99, 102, 241, 0.25);
            --border: rgba(255, 255, 255, 0.08);
            --border-light: rgba(255, 255, 255, 0.15);
            --text-main: #f1f5f9;
            --text-dim: #94a3b8;
            --text-muted: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --sidebar-width: 260px;
            --radius-xl: 32px;
            --radius-lg: 18px;
            --radius-md: 12px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body.presto-admin { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-deep); color: var(--text-main); font-size: 14px; -webkit-font-smoothing: antialiased; letter-spacing: -0.01em; }
        
        .presto-admin a { color: var(--primary); text-decoration: none; transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
        .presto-admin a:hover { color: #818cf8; }

        /* Layout Structure */
        .presto-admin-layout { display: flex; min-height: 100vh; background: radial-gradient(circle at 10% 10%, rgba(99, 102, 241, 0.08) 0%, transparent 40%), radial-gradient(circle at 90% 90%, rgba(168, 85, 247, 0.08) 0%, transparent 40%); }
        
        /* Sidebar Design */
        .presto-sidebar { width: var(--sidebar-width); background: var(--bg-side); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 1000; box-shadow: 10px 0 40px rgba(0,0,0,0.4); }
        .presto-sidebar-brand { padding: 48px 24px; font-size: 24px; font-weight: 800; display: flex; align-items: center; gap: 14px; letter-spacing: -0.04em; }
        .presto-sidebar-brand span { color: var(--primary); }
        
        .presto-nav-groups { padding: 24px 14px; flex: 1; overflow-y: auto; scrollbar-width: none; }
        .presto-nav-groups::-webkit-scrollbar { display: none; }
        .presto-nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 16px; color: var(--text-dim); text-decoration: none; font-weight: 600; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); margin-bottom: 6px; cursor: pointer; position: relative; }
        .presto-nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text-main); transform: translateX(5px); }
        .presto-nav-item.active { background: linear-gradient(135deg, rgba(99, 102, 241, 0.18) 0%, rgba(99, 102, 241, 0.06) 100%); color: var(--primary); box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.25); }
        .presto-nav-item.active::after { content: ''; position: absolute; left: 0; top: 14px; bottom: 14px; width: 4px; background: var(--primary); border-radius: 0 4px 4px 0; box-shadow: 0 0 15px var(--primary-glow); }
        
        .active-parent { color: #fff !important; }
        .nav-icon { font-size: 18px; opacity: 0.7; transition: 0.3s; }
        .presto-nav-item.active .nav-icon { opacity: 1; filter: drop-shadow(0 0 8px var(--primary-glow)); }
        .nav-chevron { margin-left: auto; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); opacity: 0.4; }
        .is-open .nav-chevron { transform: rotate(180deg); opacity: 1; color: var(--primary); }

        .presto-submenu { 
            max-height: 0; 
            overflow: hidden; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
            padding-left: 14px;
            display: flex;
            flex-direction: column;
            margin-top: -4px;
            margin-bottom: 4px;
            opacity: 0;
            pointer-events: none;
        }
        .is-open .presto-submenu { 
            max-height: 800px; 
            padding-bottom: 8px;
            opacity: 1;
            pointer-events: auto;
        }
        .presto-submenu-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 12px 18px; 
            border-radius: 12px; 
            color: var(--text-muted); 
            font-size: 13.5px; 
            font-weight: 600; 
            transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
            margin-bottom: 2px;
            border-left: 2px solid transparent;
        }
        .presto-submenu-item:hover { 
            color: var(--text-main); 
            background: rgba(255,255,255,0.04); 
            padding-left: 22px; 
            border-left-color: rgba(99, 102, 241, 0.3);
        }
        .presto-submenu-item.active { 
            color: var(--primary); 
            font-weight: 700; 
            background: rgba(99, 102, 241, 0.08); 
            border-left-color: var(--primary);
        }
        .sub-icon { font-size: 14px; opacity: 0.5; width: 20px; text-align: center; }
        .presto-submenu-item.active .sub-icon { opacity: 1; filter: drop-shadow(0 0 5px var(--primary-glow)); }
        
        .sidebar-footer { padding: 32px 14px; border-top: 1px solid var(--border); }
        .nav-user-profile { display: flex; align-items: center; gap: 14px; padding: 16px; background: rgba(0,0,0,0.3); border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .nav-user-profile .avatar { width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; color: #fff; background: linear-gradient(135deg, #6366f1, #a855f7); box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3); }
        .nav-user-profile .info { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .nav-user-profile .name { font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #fff; }
        .nav-user-profile .role { font-size: 11px; color: var(--text-muted); font-weight: 600; }
        .logout-btn { background: rgba(255,255,255,0.04); border: none; color: var(--text-muted); cursor: pointer; padding: 8px; border-radius: 10px; transition: 0.25s; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.15); color: var(--danger); transform: scale(1.1); }

        /* Main Content Wrapper */
        .presto-main-wrapper { flex: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; }
        .presto-main-header { padding: 24px 48px; border-bottom: 1px solid var(--border); background: rgba(6, 8, 12, 0.85); backdrop-filter: blur(25px); position: sticky; top: 0; z-index: 900; display: flex; align-items: center; justify-content: space-between; gap: 40px; }
        
        .header-search { flex: 1; max-width: 500px; position: relative; display: flex; align-items: center; }
        .header-search svg { position: absolute; left: 18px; color: var(--text-muted); pointer-events: none; }
        .header-search input { width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 14px 20px 14px 50px; color: var(--text-main); outline: none; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-weight: 600; }
        .header-search input:focus { border-color: var(--primary); box-shadow: 0 0 0 5px var(--primary-glow); background: rgba(0,0,0,0.45); }
        
        .header-actions { display: flex; align-items: center; gap: 32px; }
        .header-notif { position: relative; color: var(--text-muted); cursor: pointer; transition: 0.25s; }
        .header-notif:hover { color: var(--text-main); transform: translateY(-2px); }
        .header-notif .pulse { position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background: var(--danger); border-radius: 50%; border: 3px solid #06080c; }

        .presto-content-area { padding: 48px 64px; }
        .page-title { font-size: 38px; font-weight: 800; margin-bottom: 48px; letter-spacing: -0.05em; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .section-title { font-size: 24px; font-weight: 800; margin: 64px 0 32px; display: flex; align-items: center; gap: 20px; letter-spacing: -0.02em; }

        /* Premium Buttons */
        .presto-btn { display: inline-flex; align-items: center; gap: 10px; padding: 14px 32px; border-radius: var(--radius-lg); font-size: 14px; font-weight: 800; cursor: pointer; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: none; letter-spacing: -0.01em; }
        .presto-btn-primary { background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%); color: #fff; box-shadow: 0 10px 25px rgba(99, 102, 241, 0.35); }
        .presto-btn-primary:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(99, 102, 241, 0.45); filter: brightness(1.1); }
        .presto-btn-secondary { background: rgba(255,255,255,0.06); color: var(--text-main); border: 1px solid var(--border); backdrop-filter: blur(10px); }
        .presto-btn-secondary:hover { background: rgba(255,255,255,0.1); border-color: var(--border-light); transform: translateY(-2px); }

        /* Luxury Cards */
        .presto-card { background: var(--bg-card); border-radius: var(--radius-xl); border: 1px solid var(--border); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; backdrop-filter: blur(25px); }
        .presto-card:hover { border-color: var(--border-light); background: var(--bg-card-hover); transform: translateY(-8px); box-shadow: 0 40px 80px rgba(0,0,0,0.5); }
        .presto-card-header { padding: 28px 40px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02); }
        .presto-card-title { font-size: 20px; font-weight: 800; letter-spacing: -0.02em; }
        .presto-card-body { padding: 40px; }

        /* Premium Form Controls */
        .presto-form { display: flex; flex-direction: column; gap: 32px; }
        .presto-field-group { display: flex; flex-direction: column; gap: 10px; width: 100%; }
        .presto-field-label { font-size: 13px; font-weight: 700; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.1em; }
        .presto-field-hint { font-size: 12px; color: var(--text-muted); margin-top: 6px; font-style: italic; }
        
        .presto-input, .presto-select, .presto-textarea {
            width: 100%; background: rgba(0,0,0,0.2); border: 1px solid var(--border);
            border-radius: 14px; padding: 16px 20px; color: #fff; font-size: 15px;
            font-weight: 600; outline: none; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(5px);
        }
        .presto-input:focus, .presto-select:focus, .presto-textarea:focus {
            border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-glow);
            background: rgba(0,0,0,0.35);
        }
        .presto-input::placeholder { color: var(--text-muted); opacity: 0.5; }
        
        .presto-select { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 20px center; padding-right: 50px; }
        
        .presto-textarea { min-height: 120px; resize: vertical; line-height: 1.6; }

        .presto-form-section-head { margin: 48px 0 24px; padding-bottom: 16px; border-bottom: 2px solid var(--primary); display: flex; align-items: center; gap: 15px; }
        .presto-form-section-head h3 { font-size: 20px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
        .presto-form-section-head .icon-wrap { width: 36px; height: 36px; border-radius: 10px; background: var(--primary-glow); display: flex; align-items: center; justify-content: center; font-size: 18px; }

        /* Grid System Utility */
        .presto-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 32px; }
        .col-12 { grid-column: span 12; }
        .col-6 { grid-column: span 6; }
        .col-4 { grid-column: span 4; }
        .col-8 { grid-column: span 8; }

        /* Modern Checkbox list */
        .presto-check-list { background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 18px; padding: 24px; display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; margin-top: 10px; }
        .presto-check-item { display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 12px; border-radius: 12px; transition: 0.25s; }
        .presto-check-item:hover { background: rgba(255,255,255,0.05); }
        .presto-check-item input[type="checkbox"] { width: 18px; height: 18px; border-radius: 6px; appearance: none; background: rgba(0,0,0,0.3); border: 1px solid var(--border); cursor: pointer; position: relative; transition: 0.3s; }
        .presto-check-item input[type="checkbox"]:checked { background: var(--primary); border-color: var(--primary); }
        .presto-check-item input[type="checkbox"]:checked::after { content: "✓"; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); color: #fff; font-weight: 900; font-size: 11px; }
        .presto-check-label { font-size: 14px; font-weight: 600; color: var(--text-dim); flex: 1; }
        .presto-check-item:hover .presto-check-label { color: #fff; }
        .presto-check-badge { font-size: 10px; background: rgba(255,255,255,0.1); padding: 2px 7px; border-radius: 6px; color: var(--text-muted); text-transform: uppercase; margin-left: auto; }

        /* Advanced Stat Cards */
        .presto-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px; }
        .stat-card-premium { padding: 40px; display: flex; align-items: flex-start; justify-content: space-between; }
        .stat-label { font-size: 13px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 18px; display: block; opacity: 0.8; }
        .stat-value { font-size: 48px; font-weight: 800; line-height: 1; margin-bottom: 18px; display: block; color: #fff; background: linear-gradient(to bottom, #fff 40%, #94a3b8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -0.05em; }
        .stat-trend { font-size: 12px; font-weight: 800; background: rgba(16, 185, 129, 0.15); color: var(--success); padding: 6px 14px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.1); }
        .stat-trend.trend-neutral { background: rgba(255,255,255,0.08); color: var(--text-dim); }
        .stat-icon-wrap { width: 68px; height: 68px; border-radius: 22px; background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 100%); display: flex; align-items: center; justify-content: center; font-size: 30px; box-shadow: inset 0 0 25px rgba(255,255,255,0.03); }

        /* Data Tables Design */
        .presto-table-topbar { display: flex; align-items: center; justify-content: space-between; gap: 32px; margin-bottom: 48px; width: 100%; }
        .presto-table-topbar-actions { display: flex; align-items: center; }
        
        .presto-search-box { display: flex; gap: 12px; align-items: center; }
        .search-input-wrap { position: relative; display: flex; align-items: center; flex: 1; min-width: 320px; }
        .search-icon { position: absolute; left: 18px; color: var(--text-muted); pointer-events: none; z-index: 10; }
        .search-input-wrap input { width: 100%; height: 52px; background: rgba(0,0,0,0.25); border: 1px solid var(--border); border-radius: 16px; padding: 0 20px 0 52px; color: #fff; font-size: 14px; font-weight: 600; outline: none; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); backdrop-filter: blur(10px); }
        .search-input-wrap input:focus { border-color: var(--primary); box-shadow: 0 0 0 5px var(--primary-glow); background: rgba(0,0,0,0.4); }
        .presto-search-box .presto-btn { height: 52px; padding: 0 32px; white-space: nowrap; }

        .presto-table-wrap { background: var(--bg-card); border-radius: var(--radius-xl); border: 1px solid var(--border); overflow: hidden; backdrop-filter: blur(25px); box-shadow: 0 25px 50px rgba(0,0,0,0.4); margin-bottom: 40px; }
        .presto-list-table { width: 100%; display: flex; flex-direction: column; }
        .table-tr { display: flex; align-items: center; border-bottom: 1px solid var(--border); transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .table-head .table-tr { background: rgba(0,0,0,0.2); }
        .table-body .table-tr:hover { background: rgba(255,255,255,0.035); }
        .table-body .table-tr:last-child { border-bottom: none; }
        
        .table-th, .table-td { padding: 28px 48px; flex: 1; min-width: 0; position: relative; }
        .table-th { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.15em; }
        .table-td { font-size: 15px; color: var(--text-main); font-weight: 600; }
        .table-td strong { color: #fff; font-weight: 800; }
        
        .column-primary { flex: 2; }
        .check-column { flex: 0 0 100px; padding: 28px 0 !important; display: flex; align-items: center; justify-content: center; }
        .check-column input[type="checkbox"] { width: 18px; height: 18px; border-radius: 6px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); appearance: none; cursor: pointer; position: relative; transition: 0.3s; }
        .check-column input[type="checkbox"]:checked { background: var(--primary); border-color: var(--primary); }
        .check-column input[type="checkbox"]:checked::after { content: "✓"; position: absolute; color: #fff; font-size: 11px; font-weight: 900; left: 50%; top: 50%; transform: translate(-50%, -50%); }
        
        /* Modern Filter Tabs */
        .presto-subsubsub { display: flex; list-style: none; gap: 8px; background: rgba(0,0,0,0.25); padding: 8px; border-radius: 18px; border: 1px solid var(--border); margin-bottom: 0px; width: fit-content; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: 52px; align-items: center; }
        .presto-subsubsub li a { display: block; padding: 10px 24px; border-radius: 12px; font-size: 14px; font-weight: 800; color: var(--text-muted); text-decoration: none; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .presto-subsubsub li a:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .presto-subsubsub li a.current { background: var(--primary); color: #fff; box-shadow: 0 8px 20px var(--primary-glow); }
        .presto-subsubsub .count { opacity: 0.7; font-size: 11px; margin-left: 8px; font-weight: 800; background: rgba(255,255,255,0.15); padding: 2px 8px; border-radius: 8px; color: #fff; }

        /* Pagination Flow */
        .tablenav { display: flex; align-items: center; justify-content: space-between; padding: 28px 40px; background: rgba(0,0,0,0.15); }
        .tablenav.top { border-bottom: 1px solid var(--border); }
        .tablenav.bottom { border-top: 1px solid var(--border); }
        .tablenav select { background: rgba(0,0,0,0.4); border: 1px solid var(--border); border-radius: 14px; padding: 12px 20px; color: #fff; font-weight: 700; outline: none; margin-right: 20px; cursor: pointer; font-size: 13px; }
        
        .tablenav-pages { display: flex; align-items: center; gap: 24px; font-weight: 800; color: var(--text-muted); font-size: 14px; }
        .pagination-links { display: flex; gap: 8px; }
        .pagination-links .button { min-width: 44px; height: 44px; background: rgba(255,255,255,0.06); border: 1px solid var(--border); border-radius: 15px; display: inline-flex; align-items: center; justify-content: center; color: #fff; transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); font-size: 22px; }
        .pagination-links .button:hover { background: var(--primary); border-color: var(--primary); transform: translateY(-4px); box-shadow: 0 10px 20px var(--primary-glow); }

        /* Interactive Row Actions */
        .row-actions { opacity: 0; transition: 0.3s; margin-top: 12px; font-size: 12px; display: flex; gap: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; }
        .table-tr:hover .row-actions { opacity: 0.95; }
        .row-actions a { color: var(--text-muted); text-decoration: none; }
        .row-actions a:hover { color: var(--primary); text-shadow: 0 0 10px var(--primary-glow); }
        .row-actions .action-delete a:hover { color: var(--danger); text-shadow: 0 0 10px rgba(239, 68, 68, 0.3); }

        /* Custom UI Components */
        .badge { display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 12px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .badge-active { background: rgba(16, 185, 129, 0.18); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.1); }
        .badge-software { background: rgba(99, 102, 241, 0.18); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.1); }
        .badge-membership { background: rgba(245, 158, 11, 0.18); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.1); }
        
        .presto-notice { padding: 18px 24px; border-radius: 18px; margin-bottom: 32px; font-weight: 700; display: flex; align-items: center; gap: 14px; border: 1px solid var(--border); backdrop-filter: blur(10px); }
        .presto-notice-info { background: rgba(99, 102, 241, 0.1); color: var(--primary); border-color: rgba(99, 102, 241, 0.2); }
        .presto-notice-success { background: rgba(16, 185, 129, 0.1); color: var(--success); border-color: rgba(16, 185, 129, 0.2); }

        /* Dynamic Visuals */
        .donut-chart-mock { width: 220px; height: 220px; border-radius: 50%; background: conic-gradient(var(--primary) 0% 45%, var(--success) 45% 75%, var(--warning) 75% 92%, #ec4899 92% 100%); margin: 48px auto; position: relative; display: flex; align-items: center; justify-content: center; box-shadow: 0 30px 60px rgba(0,0,0,0.6); }
        .donut-chart-mock::before { content: ""; width: 160px; height: 160px; background: #0b0e14; border-radius: 50%; position: absolute; box-shadow: inset 0 0 30px rgba(0,0,0,0.5); }
        .donut-inner { position: relative; font-size: 32px; font-weight: 800; color: #fff; letter-spacing: -0.06em; }

        /* Product Categories Grid & Cards */
        .presto-category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 32px; margin: 40px 0 64px; }
        .category-card { padding: 32px; display: flex; flex-direction: column; height: 100%; border-radius: 28px; }
        .category-card:hover { transform: translateY(-6px); }
        
        .cat-header { display: flex; align-items: center; gap: 20px; margin-bottom: 28px; }
        .cat-icon { width: 56px; height: 56px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 26px; box-shadow: 0 10px 20px rgba(0,0,0,0.3); flex-shrink: 0; }
        .cat-title-group h3 { font-size: 18px; font-weight: 800; margin-bottom: 4px; color: #fff; letter-spacing: -0.02em; }
        
        .cat-stats { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; flex: 1; }
        .cat-stat { display: flex; justify-content: space-between; font-size: 14px; color: var(--text-dim); font-weight: 600; }
        .cat-stat strong { color: #fff; font-weight: 700; }
        
        .cat-progress { margin-top: auto; padding-top: 20px; }
        .progress-label { display: flex; justify-content: space-between; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; color: var(--text-muted); }
        .progress-bar { height: 8px; background: rgba(0,0,0,0.3); border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 4px; background: linear-gradient(to right, var(--primary), #a855f7); box-shadow: 0 0 15px var(--primary-glow); }
        
        .cat-footer { margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); }
        .btn-ghost-sm { background: rgba(255,255,255,0.03); border: 1px solid var(--border); color: var(--text-dim); font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; padding: 10px 16px; border-radius: 12px; width: 100%; text-align: center; }
        .btn-ghost-sm:hover { background: rgba(255,255,255,0.08); color: #fff; border-color: var(--border-light); }

        /* Dashboard Bottom Row & Components */
        .dashboard-bottom-row { display: grid; grid-template-columns: 2fr 1fr; gap: 40px; margin-top: 80px; }
        .card-tabs { display: flex; gap: 10px; background: rgba(0,0,0,0.35); padding: 8px; border-radius: 18px; border: 1px solid var(--border); }
        .tab-btn { background: none; border: none; color: var(--text-muted); padding: 10px 24px; border-radius: 12px; font-size: 13px; font-weight: 800; cursor: pointer; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .tab-btn:hover { color: var(--text-main); background: rgba(255,255,255,0.08); }
        .tab-btn.active { background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%); color: #fff; box-shadow: 0 8px 20px var(--primary-glow); }
        
        .chart-legend { list-style: none; padding: 40px 0 0; display: flex; flex-direction: column; gap: 20px; }
        .chart-legend li { display: flex; align-items: center; justify-content: space-between; font-size: 15px; font-weight: 700; color: var(--text-dim); }
        .chart-legend .dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 16px; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
        .chart-legend span { display: flex; align-items: center; }
        
        /* Utility Classes */
        .p-0 { padding: 0 !important; }
        .mt-64 { margin-top: 64px; }

        /* Footer */
        .presto-admin-footer { margin-top: 80px; padding-top: 32px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 13px; font-weight: 600; }
        .presto-admin-footer strong { color: var(--text-dim); }
        CSS;
    }

    protected function adminJs(): string
    {
        return <<<JS
        // Bulk action handler
        document.querySelectorAll('.presto-bulk-apply').forEach(btn => {
            btn.addEventListener('click', () => {
                const position = btn.dataset.position;
                const select   = document.querySelector('#bulk-action-selector-' + position);
                const action   = select ? select.value : '-1';
                if (action === '-1') { alert('Please select a bulk action.'); return; }
                const checked  = [...document.querySelectorAll('input[name="item[]"]:checked')].map(c => c.value);
                if (!checked.length) { alert('Please select at least one item.'); return; }
                const confirm  = select.options[select.selectedIndex].dataset.confirm;
                if (confirm && !window.confirm(confirm)) return;
                fetch('/api/' + window.location.pathname.split('/')[2], {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-Bulk-Action': action},
                    body: JSON.stringify({ids: checked, action})
                }).then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message || 'Error'); });
            });
        });

        // Select-all checkbox
        const selectAll = document.getElementById('cb-select-all-1');
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                document.querySelectorAll('input[name="item[]"]').forEach(c => c.checked = selectAll.checked);
            });
        }

        // Multi-level menu toggle
        document.querySelectorAll('[data-toggle="submenu"]').forEach(parent => {
            parent.addEventListener('click', () => {
                const wrapper = parent.closest('.presto-nav-group-wrapper');
                
                // Close others (Accordion style)
                document.querySelectorAll('.presto-nav-group-wrapper').forEach(other => {
                    if (other !== wrapper) other.classList.remove('is-open');
                });
                
                wrapper.classList.toggle('is-open');
            });
        });

        // Non-GET row actions
        JS;
    }
}
