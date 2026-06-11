<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Module;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Module\ModuleValidator;
use Witals\Framework\Module\Exceptions\ModuleException;

class ModuleValidatorTest extends TestCase
{
    protected ModuleValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ModuleValidator();
    }

    public function test_valid_route_module_passes(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'route',
            'version' => '1.0.0',
            'description' => 'A test module',
            'enabled' => true,
            'route_prefix' => '/api/test',
            'routes' => [
                ['method' => 'GET', 'path' => '/hello', 'handler' => ['App\\Handler', 'index']],
            ],
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertEmpty($result['errors']);
    }

    public function test_valid_support_module_passes(): void
    {
        $meta = [
            'name' => 'email',
            'type' => 'support',
            'version' => '2.1.3',
            'description' => 'Email service',
            'enabled' => true,
        ];

        $result = $this->validator->validate('/modules/email', $meta);

        $this->assertEmpty($result['errors']);
    }

    public function test_missing_name_fails(): void
    {
        $meta = [
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('missing required field', $result['errors'][0]);
    }

    public function test_empty_name_fails(): void
    {
        $meta = [
            'name' => '',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('missing required field "name"', $result['errors'][0]);
    }

    public function test_missing_type_fails(): void
    {
        $meta = [
            'name' => 'test',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('missing required field "type"', $result['errors'][0]);
    }

    public function test_invalid_type_fails(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'cli',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('invalid type "cli"', $result['errors'][0]);
    }

    public function test_missing_version_fails(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'support',
            'description' => 'test',
            'enabled' => true,
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('missing required field "version"', $result['errors'][0]);
    }

    public function test_invalid_semver_fails(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'support',
            'version' => 'abc',
            'description' => 'test',
            'enabled' => true,
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('not valid semver format', $result['errors'][0]);
    }

    public function test_missing_enabled_fails(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('missing required field "enabled"', $result['errors'][0]);
    }

    public function test_route_module_missing_prefix_fails(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'route',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('must declare "route_prefix"', $result['errors'][0]);
    }

    public function test_route_module_missing_routes_fails(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'route',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
            'route_prefix' => '/api',
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('must declare at least one route', $result['errors'][0]);
    }

    public function test_route_missing_method_fails(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'route',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
            'route_prefix' => '/api',
            'routes' => [
                ['path' => '/hello', 'handler' => ['A', 'b']],
            ],
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('missing "method"', $result['errors'][0]);
    }

    public function test_route_invalid_method_fails(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'route',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
            'route_prefix' => '/api',
            'routes' => [
                ['method' => 'INVALID', 'path' => '/hello', 'handler' => ['A', 'b']],
            ],
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('invalid HTTP method', $result['errors'][0]);
    }

    public function test_validate_runtime_dependency_missing(): void
    {
        $this->validator->setAllMetadata([]);

        $result = $this->validator->validateRuntime('test_a', [
            'name' => 'test_a',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
            'depends' => ['missing_module'],
        ], []);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('depends on "missing_module"', $result['errors'][0]);
    }

    public function test_validate_runtime_consumes_mismatch(): void
    {
        $allMetadata = [
            'other' => [
                'name' => 'other',
                'type' => 'support',
                'version' => '1.0.0',
                'description' => 'other',
                'enabled' => true,
                'provides' => ['service_a', 'service_b'],
            ],
        ];

        $result = $this->validator->validateRuntime('test', [
            'name' => 'test',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
            'consumes' => ['service_a', 'service_c'],
        ], $allMetadata);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('consumes "service_c"', $result['errors'][0]);
    }

    public function test_validate_warns_when_no_psr4_autoload(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ];

        $result = $this->validator->validate('/modules/test', $meta);

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('no PSR-4 autoload declared', $result['warnings'][0]);
    }
}
