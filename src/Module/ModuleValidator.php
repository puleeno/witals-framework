<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Module\Exceptions\ModuleException;

class ModuleValidator
{
    protected array $allMetadata = [];

    public function setAllMetadata(array $metadata): void
    {
        $this->allMetadata = $metadata;
    }

    public function validate(string $modulePath, array $metadata): array
    {
        $errors = [];
        $warnings = [];
        $name = $metadata['name'] ?? basename($modulePath);

        try {
            $this->validateRequiredFields($name, $metadata);
            $this->validateType($name, $metadata);
            $this->validateVersion($name, $metadata);
            $this->validateRouteConfiguration($name, $metadata);
            $this->validateRoutes($name, $metadata);

            if (!isset($metadata['autoload']['psr-4'])) {
                $warnings[] = "[Module] \"{$name}\": no PSR-4 autoload declared — classes may not be found.";
            }
        } catch (ModuleException $e) {
            $errors[] = $e->getMessage();
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'name' => $name];
    }

    public function validateRuntime(string $name, array $metadata, array $allMetadata): array
    {
        $errors = [];
        $warnings = [];

        try {
            $this->validateDependencies($name, $metadata, $allMetadata);
            $this->validateProvidesConsumes($name, $metadata, $allMetadata);
        } catch (ModuleException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $this->validateProvidersExist($name, $metadata);
            $this->validateBootstrapExists($name, $metadata);
            $this->validateHandlersExist($name, $metadata);
        } catch (ModuleException $e) {
            $errors[] = $e->getMessage();
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'name' => $name];
    }

    protected function validateRequiredFields(string $name, array $metadata): void
    {
        $required = ['name', 'type', 'version', 'description'];

        foreach ($required as $field) {
            if (!isset($metadata[$field]) || (is_string($metadata[$field]) && $metadata[$field] === '')) {
                throw ModuleException::missingField($name, $field);
            }
        }

        if (!isset($metadata['enabled'])) {
            throw ModuleException::missingField($name, 'enabled');
        }
    }

    protected function validateType(string $name, array $metadata): void
    {
        $valid = ['route', 'support'];

        if (!in_array($metadata['type'], $valid, true)) {
            throw ModuleException::invalidType($name, $metadata['type']);
        }
    }

    protected function validateVersion(string $name, array $metadata): void
    {
        $version = $metadata['version'];

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            throw ModuleException::invalidSemver($name, $version);
        }
    }

    protected function validateRouteConfiguration(string $name, array $metadata): void
    {
        if ($metadata['type'] !== 'route') {
            return;
        }

        if (!isset($metadata['route_prefix']) || $metadata['route_prefix'] === '') {
            throw ModuleException::routeWithoutPrefix($name);
        }

        if (!isset($metadata['routes']) || !is_array($metadata['routes']) || $metadata['routes'] === []) {
            throw ModuleException::routeWithoutRoutes($name);
        }
    }

    protected function validateRoutes(string $name, array $metadata): void
    {
        $routes = $metadata['routes'] ?? [];

        foreach ($routes as $i => $route) {
            if (!isset($route['method'])) {
                throw ModuleException::invalidRouteEntry($name, $i, 'missing "method"');
            }

            if (!in_array(strtoupper($route['method']), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], true)) {
                throw ModuleException::invalidRouteEntry($name, $i, sprintf('invalid HTTP method "%s"', $route['method']));
            }

            if (!isset($route['path'])) {
                throw ModuleException::invalidRouteEntry($name, $i, 'missing "path"');
            }

            if (!isset($route['handler'])) {
                throw ModuleException::invalidRouteEntry($name, $i, 'missing "handler"');
            }
        }
    }

    protected function validateDependencies(string $name, array $metadata, array $allMetadata): void
    {
        $depends = $metadata['depends'] ?? [];

        foreach ($depends as $dep) {
            if (!isset($allMetadata[$dep])) {
                throw ModuleException::dependsOnMissing($name, $dep);
            }

            if (!($allMetadata[$dep]['enabled'] ?? false)) {
                throw ModuleException::dependsOnDisabled($name, $dep);
            }
        }
    }

    protected function validateProvidesConsumes(string $name, array $metadata, array $allMetadata): void
    {
        $consumes = $metadata['consumes'] ?? [];
        if ($consumes === []) {
            return;
        }

        $allProvides = [];

        foreach ($allMetadata as $modName => $modMeta) {
            if ($modName === $name) {
                continue;
            }

            if (!($modMeta['enabled'] ?? false)) {
                continue;
            }

            $provides = $modMeta['provides'] ?? [];
            $allProvides = array_merge($allProvides, $provides);
        }

        foreach ($consumes as $consumed) {
            if (!in_array($consumed, $allProvides, true)) {
                throw ModuleException::consumedProvidesMismatch($name, $consumed, $allProvides);
            }
        }
    }

    protected function validateProvidersExist(string $name, array $metadata): void
    {
        $providers = $metadata['providers'] ?? [];

        foreach ($providers as $provider) {
            if (!class_exists($provider)) {
                throw ModuleException::providerNotFound($name, $provider);
            }
        }
    }

    protected function validateBootstrapExists(string $name, array $metadata): void
    {
        $bootstrap = $metadata['bootstrap'] ?? null;

        if ($bootstrap !== null && $bootstrap !== '') {
            if (!class_exists($bootstrap)) {
                throw ModuleException::bootstrapNotFound($name, $bootstrap);
            }
        }
    }

    protected function validateHandlersExist(string $name, array $metadata): void
    {
        $routes = $metadata['routes'] ?? [];

        foreach ($routes as $i => $route) {
            $handler = $route['handler'] ?? null;

            if (!is_array($handler) || count($handler) !== 2) {
                continue;
            }

            [$class, $method] = $handler;

            if (!class_exists($class)) {
                throw ModuleException::handlerClassNotFound($name, $class);
            }

            if (!method_exists($class, $method)) {
                throw ModuleException::handlerMethodNotFound($name, $class, $method);
            }
        }
    }
}
