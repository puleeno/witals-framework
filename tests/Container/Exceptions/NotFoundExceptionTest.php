<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Container\Exceptions;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Container\Exceptions\NotFoundException;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundExceptionTest extends TestCase
{
    public function testImplementsNotFoundExceptionInterface(): void
    {
        $exception = new NotFoundException();

        $this->assertInstanceOf(NotFoundExceptionInterface::class, $exception);
    }

    public function testIsRuntimeException(): void
    {
        $exception = new NotFoundException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testCanCreateWithMessage(): void
    {
        $exception = new NotFoundException('Test message');

        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testCanCreateWithMessageAndCode(): void
    {
        $exception = new NotFoundException('Test message', 100);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(100, $exception->getCode());
    }

    public function testCanCreateWithPreviousException(): void
    {
        $previous = new \Exception('Previous');
        $exception = new NotFoundException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
