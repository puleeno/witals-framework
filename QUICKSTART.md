# Quick Start Guide

Get your Witals application up and running in a few simple steps.

## 1. Choose Your Environment

### Traditional (PHP-FPM / Built-in Server)
Ideal for shared hosting or simple deployments.

```bash
# Start PHP built-in server for development
php -S localhost:8000 -t public
```

### High-Performance (The Witals Way)
Harness the power of long-running processes for maximum throughput.

```bash
# General purpose server (auto-detects available engine)
php witals serve

# Force a specific engine
php witals serve --swoole      # Requires ext-swoole
php witals serve --reactphp    # Requires react/http
php witals serve --roadrunner  # Requires spiral/roadrunner
```

## 2. Global Options

The `witals serve` command supports several flags:

- `--host=0.0.0.0`: The IP address to bind to.
- `--port=8080`: The port to listen on.
- `--reactphp`: Use ReactPHP engine.
- `--swoole`: Use Swoole engine.
- `--openswoole`: Use OpenSwoole engine.

## 3. Server Configuration

### Swoole / OpenSwoole
You can optimize worker count and coroutines for high-concurrency:

```bash
# Use custom port and host
php witals serve --swoole --port=9000 --host=127.0.0.1
```

### RoadRunner
Requires a `.rr.yaml` configuration file in your project root.

```bash
./rr serve
```

## 4. Production Deployment

### Using Systemd
Create a service file at `/etc/systemd/system/witals-app.service`:

```ini
[Unit]
Description=Witals Application
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/your-app
ExecStart=/usr/bin/php /var/www/your-app/witals serve --swoole
Restart=always

[Install]
WantedBy=multi-user.target
```

### Using Docker
```dockerfile
FROM php:8.2-cli
RUN pecl install swoole && docker-php-ext-enable swoole
WORKDIR /app
COPY . .
RUN composer install --no-dev
EXPOSE 8080
CMD ["php", "witals", "serve", "--swoole"]
```

## 5. Next Steps

- 🚀 **Routing**: Define your routes in the Kernel.
- 🏗️ **Controllers**: Build your application logic.
- 💉 **DI**: Register your services in providers.
- 📈 **Performance**: Read [RUNTIME.md](./RUNTIME.md) for optimization tips.
