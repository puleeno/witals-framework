<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Serializer;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Serializer\ArraySerializer;

class ArraySerializerTest extends TestCase
{
    protected ArraySerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new ArraySerializer();
    }

    public function test_serialize_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ArraySerializer cannot produce string output. Use JsonSerializer instead.');

        $this->serializer->serialize(['foo' => 'bar']);
    }

    public function test_deserialize_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ArraySerializer cannot parse string input. Use JsonSerializer instead.');

        $this->serializer->deserialize('{"foo":"bar"}');
    }

    public function test_normalize_array(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $result = $this->serializer->normalize($data);
        $this->assertSame(['foo' => 'bar', 'baz' => 123], $result);
    }

    public function test_normalize_nested_array(): void
    {
        $data = ['foo' => ['bar' => 'baz'], 'items' => [1, 2, 3]];
        $result = $this->serializer->normalize($data);
        $this->assertSame(['foo' => ['bar' => 'baz'], 'items' => [1, 2, 3]], $result);
    }

    public function test_normalize_object(): void
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $obj->baz = 123;

        $result = $this->serializer->normalize($obj);
        $this->assertSame(['foo' => 'bar', 'baz' => 123], $result);
    }

    public function test_normalize_object_with_private_property(): void
    {
        $obj = new TestClassWithPrivate();
        $obj->setPrivate('secret');
        $obj->public = 'visible';

        $result = $this->serializer->normalize($obj);
        $this->assertArrayHasKey('private', $result);
        $this->assertArrayHasKey('public', $result);
        $this->assertSame('secret', $result['private']);
        $this->assertSame('visible', $result['public']);
    }

    public function test_normalize_nested_object(): void
    {
        $inner = new \stdClass();
        $inner->value = 'test';

        $outer = new \stdClass();
        $outer->inner = $inner;
        $outer->count = 5;

        $result = $this->serializer->normalize($outer);
        $this->assertSame(['inner' => ['value' => 'test'], 'count' => 5], $result);
    }

    public function test_normalize_string(): void
    {
        $result = $this->serializer->normalize('test');
        $this->assertSame(['test'], $result);
    }

    public function test_normalize_integer(): void
    {
        $result = $this->serializer->normalize(42);
        $this->assertSame([42], $result);
    }

    public function test_normalize_boolean(): void
    {
        $result = $this->serializer->normalize(true);
        $this->assertSame([true], $result);
    }

    public function test_normalize_null(): void
    {
        $result = $this->serializer->normalize(null);
        $this->assertSame([null], $result);
    }

    public function test_denormalize_object(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $result = $this->serializer->denormalize($data, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('bar', $result->foo);
        $this->assertSame(123, $result->baz);
    }

    public function test_denormalize_custom_class(): void
    {
        $data = ['name' => 'Test', 'value' => 42];
        $result = $this->serializer->denormalize($data, TestClassForDenormalize::class);

        $this->assertInstanceOf(TestClassForDenormalize::class, $result);
        $this->assertSame('Test', $result->name);
        $this->assertSame(42, $result->value);
    }

    public function test_denormalize_ignores_nonexistent_properties(): void
    {
        $data = ['name' => 'Test', 'nonexistent' => 'value'];
        $result = $this->serializer->denormalize($data, TestClassForDenormalize::class);

        $this->assertInstanceOf(TestClassForDenormalize::class, $result);
        $this->assertSame('Test', $result->name);
        $this->assertObjectNotHasProperty('nonexistent', $result);
    }

    public function test_denormalize_with_private_properties(): void
    {
        $data = ['private' => 'secret', 'public' => 'visible'];
        $result = $this->serializer->denormalize($data, TestClassWithPrivate::class);

        $this->assertInstanceOf(TestClassWithPrivate::class, $result);
        $this->assertSame('secret', $result->getPrivate());
        $this->assertSame('visible', $result->public);
    }

    public function test_normalize_mixed_array(): void
    {
        $data = [
            'string' => 'value',
            'integer' => 123,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => (object) ['key' => 'value']
        ];

        $result = $this->serializer->normalize($data);

        $this->assertSame('value', $result['string']);
        $this->assertSame(123, $result['integer']);
        $this->assertTrue($result['boolean']);
        $this->assertNull($result['null']);
        $this->assertSame([1, 2, 3], $result['array']);
        $this->assertSame(['key' => 'value'], $result['object']);
    }
}

class TestClassWithPrivate
{
    private string $private;
    public string $public;

    public function setPrivate(string $value): void
    {
        $this->private = $value;
    }

    public function getPrivate(): string
    {
        return $this->private;
    }
}

class TestClassForDenormalize
{
    public string $name;
    public int $value;
}
