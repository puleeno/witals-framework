# Hook System — Witals Framework

Hooks là cơ chế mở rộng ứng dụng theo phong cách WordPress/Drupal, cho phép các module tương tác với nhau mà không cần hardcode dependency.

## Mục lục

- [Actions vs Filters](#actions-vs-filters)
- [HookInterface Contract](#hookinterface-contract)
- [Global Helpers](#global-helpers)
- [Priority](#priority)
- [Khai báo Hook trong Module](#khai-báo-hook-trong-module)
- [Container Binding](#container-binding)
- [Best Practices](#best-practices)

---

## Actions vs Filters

| | Actions | Filters |
|---|---|---|
| Mục đích | "Làm gì đó" tại một thời điểm | "Sửa đổi dữ liệu" tại một thời điểm |
| Giá trị trả về | `void` | `mixed` (giá trị đã biến đổi) |
| Tham số | `do_action('hook', ...$args)` | `apply_filters('hook', $value, ...$args)` |
| Callback nhận | `function (...$args): void` | `function ($value, ...$args): mixed` |
| Ví dụ | Gửi email sau khi đăng ký | Thay đổi tiêu đề bài viết trước khi render |

**Action** — thông báo một sự kiện đã xảy ra, mọi listener chạy nhưng không thay đổi dữ liệu gốc.

**Filter** — cho phép các listener biến đổi một giá trị theo pipeline (giá trị đầu ra của callback trước là đầu vào của callback sau).

---

## HookInterface Contract

```php
namespace Witals\Framework\Module\Contracts;

interface HookInterface
{
    // Actions
    public function addAction(string $hook, callable $callback, int $priority = 10): void;
    public function doAction(string $hook, mixed ...$args): void;
    public function removeAction(string $hook, callable $callback, int $priority = 10): void;
    public function hasAction(string $hook): bool;

    // Filters
    public function addFilter(string $hook, callable $callback, int $priority = 10): void;
    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed;
    public function removeFilter(string $hook, callable $callback, int $priority = 10): void;
    public function hasFilter(string $hook): bool;
}
```

Implementation mặc định: `Witals\Framework\Module\Hook`

---

## Global Helpers

Witals cung cấp 4 global functions:

```php
add_action(string $hook, callable $callback, int $priority = 10): void
do_action(string $hook, mixed ...$args): void

add_filter(string $hook, callable $callback, int $priority = 10): void
apply_filters(string $hook, mixed $value, mixed ...$args): mixed
```

Các helper này resolve `HookInterface` từ container (`app()`), nên nếu dự án override binding thì toàn bộ code dùng global helpers tự động chạy implementation mới.

### Ví dụ Action

```php
// Đăng ký listener
add_action('user.registered', function (int $userId, array $data) {
    Mail::sendWelcome($userId);
});

// Kích hoạt hook
do_action('user.registered', $user->id, $request->all());
```

### Ví dụ Filter

```php
// Đăng ký filter
add_filter('post.title', function (string $title, int $postId) {
    return strip_tags($title);
}, 20);

// Apply filters
$title = apply_filters('post.title', $post->title, $post->id);
```

---

## Priority

Mỗi hook có thể có nhiều listener, chạy theo thứ tự **priority tăng dần**.

```php
add_action('init', $callbackA, 10);   // chạy trước
add_action('init', $callbackB, 5);    // chạy đầu tiên (priority thấp nhất)
add_action('init', $callbackC, 20);   // chạy cuối (priority cao nhất)
```

Thứ tự thực thi: `callbackB` (5) → `callbackA` (10) → `callbackC` (20)

Priority mặc định là `10`. Giá trị càng thấp chạy càng sớm.

---

## Khai báo Hook trong Module

Khi viết module, bạn cần **document** các hook mà module cung cấp để module khác có thể sử dụng.

### Action Hook

```php
// Trong module của bạn, gọi tại vị trí mong muốn:
do_action('my_module.did_something', $data1, $data2);
```

### Filter Hook

```php
// Cho phép module khác thay đổi dữ liệu trước khi xử lý:
$config = apply_filters('my_module.config', $defaultConfig, $context);
```

### Ví dụ hoàn chỉnh

```php
class BlogModule extends Module
{
    public function boot(): void
    {
        // Module khác có thể can thiệp vào query trước khi chạy
        $query = apply_filters('blog.before_query', $query, $this->request);

        $posts = $this->db->query($query);

        // Module khác có thể biến đổi kết quả trước khi render
        $posts = apply_filters('blog.after_query', $posts, $this->request);

        do_action('blog.query_completed', $posts, $this->request);
    }
}
```

Module khác lắng nghe:

```php
// Trong module khác:
add_action('blog.query_completed', function (array $posts, Request $req) {
    Log::info('Blog query executed', ['count' => count($posts)]);
});

add_filter('blog.before_query', function (Query $query, Request $req) {
    return $query->where('status', 'published');
});
```

### Naming Convention

```
{module}.{action}
```

Ví dụ:
- `user.registered`
- `post.saved`
- `page.rendered`
- `seo.meta.generated`

Dùng dấu chấm (`.`) để phân cách, không dùng gạch dưới hay gạch ngang.

---

## Container Binding

Witals đăng ký `HookInterface` singleton trong `ModuleServiceProvider`:

```php
$this->app->singleton(HookInterface::class, Hook::class);
```

Nếu dự án của bạn cần implementation riêng (ví dụ PrestoWorld dùng `HookDispatcher` với compiled map), chỉ cần override binding **sau khi** witals registered:

```php
// Trong ServiceProvider của dự án
$this->app->singleton(HookInterface::class, MyHookDispatcher::class);
```

Khi đó toàn bộ global helpers (`add_action`, `do_action`, ...) tự động dùng implementation mới mà không cần sửa code.

---

## Best Practices

### 1. Luôn document hook signature

```php
/**
 * Action: blog.query_completed
 * 
 * Fires after a blog query is executed.
 *
 * @param array $posts  The fetched posts
 * @param Request $request  The current HTTP request
 */
do_action('blog.query_completed', $posts, $request);
```

### 2. Dùng prefix module name

Tránh xung đột: luôn bắt đầu bằng tên module.

```
✅  user.registered
❌  registered
```

### 3. Filter trả về cùng kiểu dữ liệu

Filter callback **phải** trả về đúng kiểu dữ liệu mà nó nhận vào:

```php
// ✅ Đúng — filter title trả về string
add_filter('post.title', fn(string $title) => strtoupper($title));

// ❌ Sai — filter title trả về array
add_filter('post.title', fn(string $title) => ['bad']);
```

### 4. Không lạm dụng hooks

Hooks tốt cho mở rộng, nhưng đừng biến mọi function call thành hook. Chỉ hook những điểm thực sự cần mở rộng.

### 5. removeAction / removeFilter

Dùng khi cần gỡ bỏ listener đã đăng ký (ví dụ trong test):

```php
$callback = fn() => Log::info('done');
add_action('init', $callback);
// ...
remove_action('init', $callback);
```

Cần truyền **đúng cùng instance** callback để `array_filter` so sánh được.

### 6. Không throw exception trong listener

Listener không nên throw exception — nó phá vỡ pipeline. Nếu cần, catch bên trong và log lỗi.

---

## License

MIT — Witals Framework
