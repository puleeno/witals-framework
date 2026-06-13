<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use App\Http\Middleware\CorsMiddleware;

class CorsMiddlewareTest extends TestCase
{
    private string $originsBackup;

    protected function setUp(): void
    {
        $this->originsBackup = getenv('CORS_ALLOWED_ORIGINS') ?: '';
    }

    protected function tearDown(): void
    {
        if ($this->originsBackup !== '') {
            putenv('CORS_ALLOWED_ORIGINS=' . $this->originsBackup);
        } else {
            putenv('CORS_ALLOWED_ORIGINS');
        }
    }

    public function test_constructor_reads_origins_from_env(): void
    {
        putenv('CORS_ALLOWED_ORIGINS=https://example.com,https://app.example.com');

        $ref = new \ReflectionClass(CorsMiddleware::class);
        $instance = $ref->newInstance();
        $prop = $ref->getProperty('allowedOrigins');
        $prop->setAccessible(true);

        $this->assertSame(
            ['https://example.com', 'https://app.example.com'],
            $prop->getValue($instance),
        );
    }

    public function test_constructor_wildcard_when_explicitly_set(): void
    {
        putenv('CORS_ALLOWED_ORIGINS=*');

        $ref = new \ReflectionClass(CorsMiddleware::class);
        $instance = $ref->newInstance();
        $prop = $ref->getProperty('allowedOrigins');
        $prop->setAccessible(true);

        $this->assertSame(['*'], $prop->getValue($instance));
    }

    public function test_constructor_empty_origins_in_production(): void
    {
        putenv('CORS_ALLOWED_ORIGINS');
        putenv('APP_ENV=production');

        $ref = new \ReflectionClass(CorsMiddleware::class);
        $instance = $ref->newInstance();
        $prop = $ref->getProperty('allowedOrigins');
        $prop->setAccessible(true);

        $this->assertSame([], $prop->getValue($instance));
    }

    public function test_constructor_wildcard_in_non_production(): void
    {
        putenv('CORS_ALLOWED_ORIGINS');
        putenv('APP_ENV=development');

        $ref = new \ReflectionClass(CorsMiddleware::class);
        $instance = $ref->newInstance();
        $prop = $ref->getProperty('allowedOrigins');
        $prop->setAccessible(true);

        $this->assertSame(['*'], $prop->getValue($instance));
    }
}
