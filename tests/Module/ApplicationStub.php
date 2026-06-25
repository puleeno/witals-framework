<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Module;

use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;

class ApplicationStub extends Application
{
    protected array $configCache = [];

    public function registerConfiguredProviders(): void
    {
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return $default;
    }
}

class LoggerStub implements \Psr\Log\LoggerInterface
{
    public function emergency($message, array $context = []): void {}
    public function alert($message, array $context = []): void {}
    public function critical($message, array $context = []): void {}
    public function error($message, array $context = []): void {}
    public function warning($message, array $context = []): void {}
    public function notice($message, array $context = []): void {}
    public function info($message, array $context = []): void {}
    public function debug($message, array $context = []): void {}
    public function log($level, $message, array $context = []): void {}
}
