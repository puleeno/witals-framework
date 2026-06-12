<?php

declare(strict_types=1);

namespace Witals\Framework\Session;

use Witals\Framework\Contracts\Session\SessionInterface;

class NativeSession implements SessionInterface
{
    private bool $started = false;

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $this->start();
        $_SESSION = [];
    }

    public function destroy(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }

            session_destroy();
            $this->started = false;
        }
    }

    public function getId(): string
    {
        $this->start();
        return session_id();
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }
}
