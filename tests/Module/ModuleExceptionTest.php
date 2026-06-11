<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Module;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Module\Exceptions\ModuleException;
use RuntimeException;

class ModuleExceptionTest extends TestCase
{
    public function test_missing_field(): void
    {
        $e = ModuleException::missingField('test', 'name');
        $this->assertInstanceOf(RuntimeException::class, $e);
        $this->assertStringContainsString('test', $e->getMessage());
        $this->assertStringContainsString('name', $e->getMessage());
    }

    public function test_invalid_type(): void
    {
        $e = ModuleException::invalidType('test', 'cli');
        $this->assertStringContainsString('cli', $e->getMessage());
        $this->assertStringContainsString('route', $e->getMessage());
    }

    public function test_invalid_semver(): void
    {
        $e = ModuleException::invalidSemver('test', 'abc');
        $this->assertStringContainsString('abc', $e->getMessage());
    }

    public function test_route_without_prefix(): void
    {
        $e = ModuleException::routeWithoutPrefix('test');
        $this->assertStringContainsString('route_prefix', $e->getMessage());
    }

    public function test_route_without_routes(): void
    {
        $e = ModuleException::routeWithoutRoutes('test');
        $this->assertStringContainsString('routes', $e->getMessage());
    }

    public function test_invalid_route_entry(): void
    {
        $e = ModuleException::invalidRouteEntry('test', 2, 'missing method');
        $this->assertStringContainsString('route[2]', $e->getMessage());
    }

    public function test_provider_not_found(): void
    {
        $e = ModuleException::providerNotFound('test', 'App\MissingProvider');
        $this->assertStringContainsString('MissingProvider', $e->getMessage());
    }

    public function test_bootstrap_not_found(): void
    {
        $e = ModuleException::bootstrapNotFound('test', 'App\MissingBootstrap');
        $this->assertStringContainsString('MissingBootstrap', $e->getMessage());
    }

    public function test_handler_class_not_found(): void
    {
        $e = ModuleException::handlerClassNotFound('test', 'App\MissingHandler');
        $this->assertStringContainsString('MissingHandler', $e->getMessage());
    }

    public function test_handler_method_not_found(): void
    {
        $e = ModuleException::handlerMethodNotFound('test', 'App\Handler', 'missingMethod');
        $this->assertStringContainsString('App\Handler', $e->getMessage());
        $this->assertStringContainsString('missingMethod', $e->getMessage());
    }

    public function test_depends_on_missing(): void
    {
        $e = ModuleException::dependsOnMissing('test', 'missing_dep');
        $this->assertStringContainsString('missing_dep', $e->getMessage());
    }

    public function test_depends_on_disabled(): void
    {
        $e = ModuleException::dependsOnDisabled('test', 'disabled_dep');
        $this->assertStringContainsString('disabled_dep', $e->getMessage());
    }

    public function test_circular_dependency(): void
    {
        $e = ModuleException::circularDependency('test', 'a -> b -> a');
        $this->assertStringContainsString('a -> b -> a', $e->getMessage());
    }

    public function test_invalid_json(): void
    {
        $e = ModuleException::invalidJson('test', 'parse error');
        $this->assertStringContainsString('parse error', $e->getMessage());
    }

    public function test_consumed_provides_mismatch(): void
    {
        $e = ModuleException::consumedProvidesMismatch('test', 'service_x', ['service_a', 'service_b']);
        $this->assertStringContainsString('service_x', $e->getMessage());
        $this->assertStringContainsString('service_a', $e->getMessage());
    }

    public function test_consumed_provides_mismatch_none_available(): void
    {
        $e = ModuleException::consumedProvidesMismatch('test', 'service_x', []);
        $this->assertStringContainsString('service_x', $e->getMessage());
        $this->assertStringContainsString('(none)', $e->getMessage());
    }

    public function test_module_file_not_found(): void
    {
        $e = ModuleException::moduleFileNotFound('/path/to/module.json');
        $this->assertStringContainsString('/path/to/module.json', $e->getMessage());
    }
}
