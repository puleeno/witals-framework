<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Container\Exceptions;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Container\Exceptions\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;

class BindingResolutionExceptionTest extends TestCase
{
    public function testImplementsContainerExceptionInterface(): void
    {
        $exception = new BindingResolutionException();

        $this->assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }

    public function testIsRuntimeException(): void
    {
        $exception = new BindingResolutionException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testCanCreateWithMessage(): void
    {
        $exception = new BindingResolutionException('Test message');

        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testCanCreateWithMessageAndCode(): void
    {
        $exception = new BindingResolutionException('Test message', 100);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(100, $exception->getCode());
    }

    public function testCanCreateWithPreviousException(): void
    {
        $previous = new \Exception('Previous');
        $exception = new BindingResolutionException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
