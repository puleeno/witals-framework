<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Queue\Contracts\FailedJobProviderInterface;
use Witals\Framework\Queue\FailedJob\DatabaseFailedJobProvider;

class DatabaseFailedJobProviderTest extends TestCase
{
    public function test_implements_interface(): void
    {
        $reflection = new \ReflectionClass(DatabaseFailedJobProvider::class);
        $this->assertTrue($reflection->implementsInterface(FailedJobProviderInterface::class));
    }

    public function test_constructor_defaults(): void
    {
        $provider = new DatabaseFailedJobProvider();
        $this->assertInstanceOf(DatabaseFailedJobProvider::class, $provider);
    }

    public function test_constructor_custom_values(): void
    {
        $provider = new DatabaseFailedJobProvider('custom', 'failed_table');
        $this->assertInstanceOf(DatabaseFailedJobProvider::class, $provider);
    }
}
