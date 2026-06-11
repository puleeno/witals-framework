<?php

declare(strict_types=1);

namespace Witals\Framework\Module\Exceptions;

use RuntimeException;

class ModuleException extends RuntimeException
{
    public static function missingField(string $module, string $field): self
    {
        return new self(
            sprintf('[Module] "%s": missing required field "%s" in module.json', $module, $field)
        );
    }

    public static function invalidType(string $module, string $type): self
    {
        return new self(
            sprintf(
                '[Module] "%s": invalid type "%s". Must be "route" or "support".',
                $module,
                $type
            )
        );
    }

    public static function invalidSemver(string $module, string $version): self
    {
        return new self(
            sprintf('[Module] "%s": version "%s" is not valid semver format (x.y.z).', $module, $version)
        );
    }

    public static function routeWithoutPrefix(string $module): self
    {
        return new self(
            sprintf('[Module] "%s": route modules must declare "route_prefix" in module.json.', $module)
        );
    }

    public static function routeWithoutRoutes(string $module): self
    {
        return new self(
            sprintf('[Module] "%s": route modules must declare at least one route in "routes".', $module)
        );
    }

    public static function invalidRouteEntry(string $module, int $index, string $reason): self
    {
        return new self(
            sprintf('[Module] "%s": route[%d] is invalid — %s', $module, $index, $reason)
        );
    }

    public static function providerNotFound(string $module, string $class): self
    {
        return new self(
            sprintf(
                '[Module] "%s": declared provider "%s" not found. Check the "providers" field and autoload configuration.',
                $module,
                $class
            )
        );
    }

    public static function bootstrapNotFound(string $module, string $class): self
    {
        return new self(
            sprintf(
                '[Module] "%s": declared bootstrap class "%s" not found.',
                $module,
                $class
            )
        );
    }

    public static function handlerClassNotFound(string $module, string $class): self
    {
        return new self(
            sprintf(
                '[Module] "%s": route handler class "%s" not found.',
                $module,
                $class
            )
        );
    }

    public static function handlerMethodNotFound(string $module, string $class, string $method): self
    {
        return new self(
            sprintf(
                '[Module] "%s": route handler method "%s::%s" not found.',
                $module,
                $class,
                $method
            )
        );
    }

    public static function dependsOnMissing(string $module, string $dependency): self
    {
        return new self(
            sprintf(
                '[Module] "%s": depends on "%s" but this module is not installed or not discoverable.',
                $module,
                $dependency
            )
        );
    }

    public static function dependsOnDisabled(string $module, string $dependency): self
    {
        return new self(
            sprintf(
                '[Module] "%s": depends on "%s" but this module is not enabled.',
                $module,
                $dependency
            )
        );
    }

    public static function circularDependency(string $module, string $chain): self
    {
        return new self(
            sprintf(
                '[Module] "%s": circular dependency detected. Chain: %s',
                $module,
                $chain
            )
        );
    }

    public static function invalidJson(string $module, string $error): self
    {
        return new self(
            sprintf('[Module] "%s": invalid module.json — %s', $module, $error)
        );
    }

    public static function consumedProvidesMismatch(
        string $module,
        string $consumed,
        array $available
    ): self {
        return new self(
            sprintf(
                '[Module] "%s": consumes "%s" but no enabled module provides it. Available: %s',
                $module,
                $consumed,
                $available !== [] ? implode(', ', $available) : '(none)'
            )
        );
    }

    public static function moduleFileNotFound(string $path): self
    {
        return new self(
            sprintf('[Module] module.json not found at: %s', $path)
        );
    }
}
