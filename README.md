# 🚀 Witals Framework

A modern, high-performance PHP framework designed for **dual runtime support**. Witals seamlessly adapts between **Traditional Web Servers** (PHP-FPM, Apache, Nginx) and **Long-Running Runtimes** (RoadRunner, ReactPHP, Swoole, OpenSwoole).

---

## ✨ Key Features

- 🎭 **Unified Entry Point**: One command to rule them all with `php witals serve`.
- 🔄 **Ambient Runtime Detection**: Automatically detects and adapts to its environment.
- 🏗️ **Architected for Scale**: Built-in IoC container with advanced request-isolation scopes.
- 🔒 **Stateless & Stateful Support**: Fine-grained state management tailored for each runtime.
- ⚡ **Turbocharged Performance**: Optimized for async, coroutines, and event loops.
- 📝 **Enterprise Logging**: High-performance PSR-3 logging with memory buffering and JSON support.
- 🛠️ **Developer Experience**: Modern PHP 8.1+ features with strict typing.

## 📦 Requirements

- **PHP 8.1+**
- **Composer**
- (Optional) Extensions for high-performance runtimes: `ext-swoole`, `ext-openswoole`, or `ext-roadrunner`.

## 🚀 Quick Start

### 1. Installation

```bash
composer require witals/framework
```

### 2. Traditional Serving (FPM/CGI)

Point your web server (Nginx/Apache) to `public/index.php`. Witals automatically detects the traditional runtime and handles request/response in a stateless manner.

### 3. High-Performance Serving

Witals comes with a unified binary to launch high-performance servers:

```bash
# Start with auto-detected runtime (RoadRunner > Swoole > OpenSwoole > ReactPHP)
php witals serve

# Force a specific runtime
php witals serve --swoole
php witals serve --reactphp --port=9000
```

## 🛠️ Core Concepts

### Request Lifecycle Management
Witals manages the request lifecycle through specific phases: **Init → Execute → Respond → Shutdown**.

- **Stateless**: The entire app boots and shuts down for every request.
- **Stateful**: The app boots once, handles multiple requests in an isolation scope, and cleans up after each request to prevent memory leaks.

### Dependency Injection & Scoping
The framework ensures that services resolved during a request are automatically cleaned up when the request ends.

```php
// Services resolved within handle() are request-scoped
$response = $app->handle($request); 
```

## 📚 Documentation

- [**QUICKSTART.md**](./QUICKSTART.md) - Get up and running in minutes.
- [**ARCHITECTURE.md**](./ARCHITECTURE.md) - Deep dive into the framework internals.
- [**RUNTIME.md**](./RUNTIME.md) - Detailed guide on supported runtimes.
- [**LIFECYCLE.md**](./LIFECYCLE.md) - Understanding the execution flow.

## 🤝 Contributing

We welcome contributions! Please follow the PSR-12 coding standard and ensure all tests pass before submitting a PR.

## 📄 License

The Witals Framework is open-sourced software licensed under the [MIT license](LICENSE).

---
Created with ❤️ by **Puleeno Nguyen** (puleeno@gmail.com)
