# Asset Manager — Intelligent Asset Resolution

> `Witals\Framework\Support\AssetManager`

Hệ thống quản lý CSS/JS thông minh với khả năng resolve asset theo context, dependency resolution, và tương thích với cấu trúc WordPress.

---

## 📋 Mục lục

- [Tổng quan](#tổng-quan)
- [Kiến trúc](#kiến-trúc)
- [Khởi tạo](#khởi-tạo)
- [Context & Rendering Mode](#context--rendering-mode)
- [Enqueue Assets](#enqueue-assets)
- [Handle Registry](#handle-registry)
- [Discovery Roots](#discovery-roots)
- [Dependency Resolution](#dependency-resolution)
- [Asset Resolution Pipeline](#asset-resolution-pipeline)
- [Versioning & Cache Busting](#versioning--cache-busting)
- [Manifest Support](#manifest-support)
- [Sử dụng trong View](#sử-dụng-trong-view)
- [Ví dụ thực tế](#ví-dụ-thực-tế)
- [WordPress Compatibility](#wordpress-compatibility)
- [API Reference](#api-reference)

---

## Tổng quan

AssetManager giải quyết bài toán:

1. **Dashboard (Admin)** cần dùng **external CSS/JS** (`<link>`, `<script src>`) để tận dụng browser cache.
2. **Frontend** cần dùng **internal/inline CSS/JS** (`<style>`, `<script>`) để tối ưu critical render path và SEO.
3. **Modules, Themes, Plugins** cần có thể đăng ký assets với dependencies phức tạp.
4. **Tương thích WordPress** trong tương lai (handle-based, `wp_enqueue_style`/`wp_enqueue_script`).

```
┌──────────────────────────────────────────────────────┐
│                   AssetManager                        │
│                                                       │
│  ┌─────────┐   ┌──────────┐   ┌──────────────────┐  │
│  │ Registry │   │ Enqueue  │   │ Discovery Roots  │  │
│  │ (handles)│──▶│  Queue   │──▶│  (search paths)  │  │
│  └─────────┘   └──────────┘   └──────────────────┘  │
│                      │                    │           │
│               ┌──────▼──────┐    ┌───────▼────────┐  │
│               │  Dependency │    │  resolveAsset() │  │
│               │  Topo Sort  │    │  (file lookup)  │  │
│               └──────┬──────┘    └───────┬────────┘  │
│                      │                    │           │
│               ┌──────▼────────────────────▼────────┐ │
│               │         Render Engine               │ │
│               │   external: <link>/<script src>     │ │
│               │   internal: <style>/<script> inline │ │
│               └─────────────────────────────────────┘ │
└──────────────────────────────────────────────────────┘
```

---

## Kiến trúc

AssetManager được đăng ký như **singleton** trong Application container:

```php
// Tự động đăng ký trong Application::registerBaseBindings()
$this->singleton(AssetManager::class, function ($app) {
    return new AssetManager($app);
});
```

Truy cập từ bất kỳ đâu:

```php
$assets = app(AssetManager::class);
// hoặc
$assets = app()->make(AssetManager::class);
```

---

## Khởi tạo

AssetManager tự động:
- Đặt `publicPath` từ `$app->basePath('public')`
- Đăng ký `public/` làm discovery root mặc định
- Dùng `APP_URL` từ `.env` (nếu có) hoặc relative URL

```php
// Constructor tự động chạy:
$this->publicPath = $app->basePath('public');
$this->addRoot($this->publicPath, $this->baseUrl);
```

---

## Context & Rendering Mode

### Contexts

Có 2 context chính, mỗi context có rendering mode mặc định:

| Context    | Mode       | Output                           |
|------------|------------|----------------------------------|
| `admin`    | `external` | `<link href="...">` / `<script src="...">` |
| `frontend` | `internal` | `<style>...</style>` / `<script>...</script>` |

### Chuyển context

```php
$assets->setContext('admin');    // mode = 'external', clear queue
$assets->setContext('frontend'); // mode = 'internal', clear queue
```

> **Quan trọng**: `setContext()` sẽ **xóa toàn bộ CSS/JS đã enqueue** để tránh leak giữa các context. Ví dụ: frontend CSS không bị load trong admin dashboard.

### Override mode thủ công

```php
$assets->setMode('internal'); // Force inline
$assets->setMode('external'); // Force link tags
```

---

## Enqueue Assets

### CSS

```php
// Cơ bản
$assets->enqueueCss('my-style', 'css/my-style.css');

// Với dependencies
$assets->enqueueCss('my-style', 'css/my-style.css', ['base-style']);

// Với options
$assets->enqueueCss('print-style', 'css/print.css', [], ['media' => 'print']);
```

### JavaScript

```php
// Cơ bản
$assets->enqueueJs('my-script', 'js/app.js');

// Với dependencies + options
$assets->enqueueJs('my-script', 'js/app.js', ['jquery'], [
    'defer' => true,
    'async' => true,
    'type'  => 'module',
]);
```

---

## Handle Registry

Pre-register assets để sử dụng lại bằng ID (giống WordPress `wp_register_style`):

### Đăng ký

```php
// Trong ServiceProvider::boot()
$assets->register('css', 'presto-core', 'css/admin-core.css');
$assets->register('css', 'presto-dashboard', 'css/admin-dashboard.css', ['presto-core']);

$assets->register('js', 'presto-core', 'js/admin-solid-core.js', [], ['defer' => true]);
$assets->register('js', 'chart-lib', 'js/chart.min.js');
$assets->register('js', 'dashboard-charts', 'js/dashboard-charts.js', ['chart-lib', 'presto-core']);
```

### Enqueue bằng ID

```php
// Không cần truyền path — tự lấy từ registry
$assets->enqueueCss('presto-core');
$assets->enqueueCss('presto-dashboard'); // deps tự động resolve

$assets->enqueueJs('dashboard-charts');  // sẽ load chart-lib + presto-core trước
```

### Ưu tiên

Khi enqueue bằng ID:
1. Nếu `$path === null` → tìm trong registry
2. Nếu registry có → dùng path, deps, options từ registry
3. Nếu caller override deps/options → dùng giá trị caller

```php
// Override deps khi enqueue
$assets->enqueueCss('presto-dashboard', null, ['custom-base']); // override deps
```

---

## Discovery Roots

Hệ thống tìm kiếm file asset qua nhiều "root" (thư mục gốc). Tương tự cách WordPress tìm template trong child theme → parent theme.

### Thêm root

```php
// Theme root
$assets->addRoot(
    '/path/to/themes/my-theme/assets',   // filesystem path
    '/themes/my-theme/assets'            // URL prefix
);

// Plugin root
$assets->addRoot(
    '/path/to/plugins/my-plugin/assets',
    '/plugins/my-plugin/assets'
);
```

### Thứ tự tìm kiếm

Roots được tìm **theo thứ tự ngược** (LIFO — Last In, First Out):

```
addRoot(public)           ← root[0] (mặc định, ưu tiên thấp nhất)
addRoot(parent-theme)     ← root[1]
addRoot(child-theme)      ← root[2] (ưu tiên cao nhất)
```

Khi resolve `css/style.css`:
1. Tìm trong `child-theme/css/style.css` → nếu có → dùng
2. Tìm trong `parent-theme/css/style.css` → nếu có → dùng
3. Tìm trong `public/css/style.css` → fallback

> **Ứng dụng**: Child theme có thể override CSS của parent theme mà không cần sửa code — chỉ cần đặt file cùng tên.

---

## Dependency Resolution

AssetManager dùng **Topological Sort** để đảm bảo assets load đúng thứ tự.

### Ví dụ

```php
$assets->enqueueCss('module-orders', 'css/orders.css', ['presto-dashboard']);
$assets->enqueueCss('presto-dashboard', 'css/admin-dashboard.css', ['presto-core']);
$assets->enqueueCss('presto-core', 'css/admin-core.css');
```

**Output (đúng thứ tự)**:
```html
<link id="presto-core-css" href="/css/admin-core.css?v=abc123">
<link id="presto-dashboard-css" href="/css/admin-dashboard.css?v=def456">
<link id="module-orders-css" href="/css/orders.css?v=789abc">
```

Dù enqueue theo thứ tự bất kỳ, dependency luôn load trước.

---

## Asset Resolution Pipeline

Khi render, mỗi asset đi qua pipeline:

```
path (relative)
    │
    ▼
┌─ Manifest check ──────────────────┐
│  manifest.json: {"app.css": ...}  │
└──────────────┬────────────────────┘
               │
    ▼
┌─ Discovery Roots search ──────────┐
│  roots[n] → roots[n-1] → ... [0] │
│  LIFO: child-theme → parent → pub│
└──────────────┬────────────────────┘
               │
    ▼
┌─ Return ──────────────────────────┐
│  { path: /full/path, url: /url  } │
└──────────────┬────────────────────┘
               │
    ▼
┌─ Mode decision ───────────────────┐
│  external → <link href="url?v=..">│
│  internal → <style>content</style>│
└───────────────────────────────────┘
```

**URL tuyệt đối** (http/https) sẽ bypass toàn bộ pipeline:

```php
$assets->enqueueCss('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter');
// → <link href="https://fonts.googleapis.com/css2?family=Inter">
```

---

## Versioning & Cache Busting

Tự động append `?v=` dựa trên file modification time:

```html
<link href="/css/style.css?v=b2885a65">
```

- Hash là 8 ký tự đầu của `md5(filemtime)`.
- Khi file thay đổi → hash thay đổi → browser tải bản mới.
- Không cần config, hoàn toàn tự động.

---

## Manifest Support

Hỗ trợ `public/manifest.json` (Vite, Mix, Webpack):

```json
{
    "css/app.css": "css/app.abc123.css",
    "js/app.js": "js/app.def456.js"
}
```

Khi enqueue `css/app.css`, AssetManager tự động resolve thành `css/app.abc123.css`.

---

## Sử dụng trong View

### PHP Template

```php
<!DOCTYPE html>
<html>
<head>
    <?php echo app(\Witals\Framework\Support\AssetManager::class)->renderCss(); ?>
</head>
<body>
    <!-- content -->
    <?php echo app(\Witals\Framework\Support\AssetManager::class)->renderJs(); ?>
</body>
</html>
```

### Trong Controller

```php
class MyController extends AdminController
{
    public function index(Request $request): Response
    {
        // Assets được config trong AdminController::adminPage()
        // Module có thể thêm CSS/JS riêng:
        $this->assets->enqueueCss('orders-page', 'css/orders.css', ['admin-dashboard']);
        
        return Response::html($this->adminPage('Orders', $content));
    }
}
```

---

## Ví dụ thực tế

### Admin Dashboard

```php
// AdminController::adminPage()
$this->assets->setContext('admin');

// Core styles (loaded as external <link> tags)
$this->assets->enqueueCss('admin-core', 'css/admin-core.css');
$this->assets->enqueueCss('admin-dashboard', 'css/admin-dashboard.css', ['admin-core']);

// Scripts
$this->assets->enqueueJs('admin-core', 'js/admin-solid-core.js', [], ['defer' => true]);
```

**Output**:
```html
<link rel="stylesheet" id="admin-core-css" href="/css/admin-core.css?v=b136f627" type="text/css" media="all">
<link rel="stylesheet" id="admin-dashboard-css" href="/css/admin-dashboard.css?v=b2885a65" type="text/css" media="all">
```

### Frontend Theme

```php
// Theme::boot()
$assets = app(AssetManager::class);
$assets->setContext('frontend'); // mode = 'internal' (inline)

$assets->enqueueCss('frontend-core', 'css/frontend.css');
```

**Output**:
```html
<!-- Asset: frontend-core -->
<style id="frontend-core-inline">
    /* Nội dung file frontend.css được inline trực tiếp */
    body { font-family: 'Inter', sans-serif; ... }
</style>
```

### Module enqueue thêm

```php
// Trong một module Orders
$assets = app(AssetManager::class);
$assets->enqueueCss('orders-table', 'css/modules/orders-table.css', ['admin-dashboard']);
$assets->enqueueJs('orders-bulk', 'js/modules/orders-bulk.js', ['admin-core']);
```

---

## WordPress Compatibility

AssetManager được thiết kế để tương thích với một số patterns của WordPress:

| WordPress                  | AssetManager                                |
|----------------------------|---------------------------------------------|
| `wp_register_style()`      | `$assets->register('css', $id, $path, $deps)` |
| `wp_register_script()`     | `$assets->register('js', $id, $path, $deps)`  |
| `wp_enqueue_style()`       | `$assets->enqueueCss($id)`                  |
| `wp_enqueue_script()`      | `$assets->enqueueJs($id)`                   |
| `get_template_directory()` | Discovery Roots (parent theme)              |
| `get_stylesheet_directory()`| Discovery Roots (child theme override)     |
| `wp_head()` / `wp_footer()`| `renderCss()` / `renderJs()`               |

### Bridge (tương lai)

Khi tích hợp `prestoworld/wp-bridge`, có thể map trực tiếp:

```php
function wp_enqueue_style($id, $path = null, $deps = []) {
    app(AssetManager::class)->enqueueCss($id, $path, $deps);
}

function wp_enqueue_script($id, $path = null, $deps = [], $in_footer = false) {
    app(AssetManager::class)->enqueueJs($id, $path, $deps, [
        'defer' => $in_footer
    ]);
}
```

---

## API Reference

### `addRoot(string $path, string $url): self`
Thêm một thư mục tìm kiếm asset. Root thêm sau có ưu tiên cao hơn.

### `setContext(string $context): self`
Chuyển context (`'admin'` / `'frontend'`). Tự động xóa queue và set mode.

### `setMode(string $mode): self`
Đặt rendering mode thủ công (`'external'` / `'internal'`).

### `register(string $type, string $id, string $path, array $deps = [], array $options = []): self`
Pre-register một asset handle. `$type` là `'css'` hoặc `'js'`.

### `enqueueCss(string $id, ?string $path = null, array $deps = [], array $options = []): void`
Enqueue CSS. Nếu `$path` là `null`, lookup từ registry.

### `enqueueJs(string $id, ?string $path = null, array $deps = [], array $options = []): void`
Enqueue JS. Options hỗ trợ: `defer`, `async`, `type`, `media`.

### `renderCss(): string`
Render tất cả CSS đã enqueue thành HTML (theo đúng thứ tự dependency).

### `renderJs(): string`
Render tất cả JS đã enqueue thành HTML.

### `resolveAsset(string $path): array`
Resolve một asset path thành `['path' => string, 'url' => string]`.

---

**Version:** 1.0 | **Author:** Witals Framework Team
