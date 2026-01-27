# Runtime Support Guide

Witals Framework is built from the ground up to support both traditional and modern PHP execution models. This guide covers how each runtime works and how to best utilize them.

## 🛠️ Performance Adapters

Witals uses a **Factory Pattern** to resolve the correct server adapter for your environment. Every server implements the `Witals\Framework\Contracts\Server` interface and declares whether it is **Stateful** or **Stateless**.

### 🎮 Unified CLI Control

Instead of manual scripts, use the `witals` binary to manage your runtimes:

```bash
# Auto-detect best available engine
php witals serve

# Port and Host configuration
php witals serve --host=127.0.0.1 --port=9000

# Engine specific forcing
php witals serve --swoole
php witals serve --reactphp
```

---

## 🏗️ Support Matrix

| Runtime | Type | Required Extension | Best For |
|---------|------|---------------------|----------|
| **Traditional** | Stateless | None | Standard Web Hosting |
| **RoadRunner** | Stateful | `spiral/roadrunner` | High-load APIs (Go + PHP) |
| **Swoole** | Stateful | `ext-swoole` | Extreme performance, Coroutines |
| **OpenSwoole**| Stateful | `ext-openswoole` | Community-driven high performance |
| **ReactPHP** | Stateful | `react/http` | Event-driven, Async I/O |

---

## 🚀 Runtime Details

### 1. Traditional (Stateless)
The default runtime for PHP-FPM, Apache, and Nginx. 
- **Lifecycle**: Entire application boots and shuts down per request.
- **State**: No state persists between requests.
- **Adapter**: `Witals\Framework\Server\TraditionalServer`.

### 2. RoadRunner (Stateful)
An high-performance application server written in Go.
- **Lifecycle**: PHP workers persist across multiple requests.
- **Adapter**: `Witals\Framework\Server\RoadRunnerServer`.

### 3. Swoole & OpenSwoole (Stateful)
C-level extensions that turn PHP into a concurrent runtime.
- **Lifecycle**: Single process handles thousands of requests via coroutines.
- **Adapter**: `Witals\Framework\Server\SwooleServer` / `OpenSwooleServer`.

### 4. ReactPHP (Stateful)
Event-driven, non-blocking I/O using an event loop.
- **Lifecycle**: Event loop persists and handles requests asynchronously.
- **Adapter**: `Witals\Framework\Server\ReactPhpServer`.

---

## 🧠 Memory & Isolation

When running in **Stateful** mode (RoadRunner, Swoole, etc.), Witals provides an **Isolation Scope** for every request.

```php
// Phase: Init
// - onRequestStart() hook called
// - Request object bound to container

// Phase: Execute
// - Middlewares run
// - Controller logic runs
// - Any services resolved here are "Request-Scoped"

// Phase: Shutdown
// - onRequestEnd() hook called
// - Container flushes request-scoped instances
// - gc_collect_cycles() triggered
```

## ⚠️ Best Practices

1. **Avoid Superglobals**: Never use `$_GET`, `$_POST` or `$_SESSION` directly. Always use the `Witals\Framework\Http\Request` object.
2. **Stateless Singletons**: Don't store request-specific data in global singleton properties.
3. **Database Connections**: Use persistent connections or connection pools (handled automatically by Swoole/OpenSwoole adapters).
4. **Cleanup**: Use the `afterRequest` lifecycle hook if you need manual cleanup of non-container resources.
