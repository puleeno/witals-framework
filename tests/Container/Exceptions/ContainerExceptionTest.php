<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Container\Exceptions;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Container\Exceptions\ContainerException;
use Psr\Container\ContainerExceptionInterface;

class ContainerExceptionTest extends TestCase
{
    public function testImplementsContainerExceptionInterface(): void
    {
        $exception = new ContainerException();

        $this->assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }

    public function testIsRuntimeException(): void
    {
        $exception = new ContainerException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testCanCreateWithMessage(): void
    {
        $exception = new ContainerException('Test message');

        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testCanCreateWithMessageAndCode(): void
    {
        $exception = new ContainerException('Test message', 100);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(100, $exception->getCode());
    }

    public function testCanCreateWithPreviousException(): void
    {
        $previous = new \Exception('Previous');
        $exception = new ContainerException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
