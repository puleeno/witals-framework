<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Database\Crud;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Database\Crud\CrudServiceProvider;

class CrudServiceProviderTest extends TestCase
{
    public function testRegisterDoesNothing(): void
    {
        $provider = new CrudServiceProvider();

        $this->expectNotToPerformAssertions();

        $provider->register();
    }

    public function testBootDoesNothing(): void
    {
        $provider = new CrudServiceProvider();

        $this->expectNotToPerformAssertions();

        $provider->boot();
    }
}
