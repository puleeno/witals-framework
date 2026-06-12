<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Serializer;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Serializer\JsonSerializer;
use Witals\Framework\Serializer\ArraySerializer;

class JsonSerializerTest extends TestCase
{
    protected JsonSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new JsonSerializer();
    }

    public function test_serialize_array(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $result = $this->serializer->serialize($data);
        $this->assertSame('{"foo":"bar","baz":123}', $result);
    }

    public function test_serialize_object(): void
    {
        $data = (object) ['foo' => 'bar'];
        $result = $this->serializer->serialize($data);
        $this->assertSame('{"foo":"bar"}', $result);
    }

    public function test_serialize_string(): void
    {
        $data = 'test string';
        $result = $this->serializer->serialize($data);
        $this->assertSame('"test string"', $result);
    }

    public function test_serialize_integer(): void
    {
        $data = 42;
        $result = $this->serializer->serialize($data);
        $this->assertSame('42', $result);
    }

    public function test_serialize_boolean(): void
    {
        $result = $this->serializer->serialize(true);
        $this->assertSame('true', $result);

        $result = $this->serializer->serialize(false);
        $this->assertSame('false', $result);
    }

    public function test_serialize_null(): void
    {
        $result = $this->serializer->serialize(null);
        $this->assertSame('null', $result);
    }

    public function test_deserialize_array(): void
    {
        $json = '{"foo":"bar","baz":123}';
        $result = $this->serializer->deserialize($json);
        $this->assertSame(['foo' => 'bar', 'baz' => 123], $result);
    }

    public function test_deserialize_nested_array(): void
    {
        $json = '{"foo":{"bar":"baz"},"items":[1,2,3]}';
        $result = $this->serializer->deserialize($json);
        $this->assertSame(['foo' => ['bar' => 'baz'], 'items' => [1, 2, 3]], $result);
    }

    public function test_deserialize_string(): void
    {
        $json = '"test string"';
        $result = $this->serializer->deserialize($json);
        $this->assertSame('test string', $result);
    }

    public function test_deserialize_integer(): void
    {
        $json = '42';
        $result = $this->serializer->deserialize($json);
        $this->assertSame(42, $result);
    }

    public function test_deserialize_boolean(): void
    {
        $result = $this->serializer->deserialize('true');
        $this->assertTrue($result);

        $result = $this->serializer->deserialize('false');
        $this->assertFalse($result);
    }

    public function test_deserialize_null(): void
    {
        $result = $this->serializer->deserialize('null');
        $this->assertNull($result);
    }

    public function test_normalize_array(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $result = $this->serializer->normalize($data);
        $this->assertSame(['foo' => 'bar', 'baz' => 123], $result);
    }

    public function test_normalize_object(): void
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $obj->baz = 123;

        $result = $this->serializer->normalize($obj);
        $this->assertSame(['foo' => 'bar', 'baz' => 123], $result);
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
        $result = $this->serializer->denormalize($data, TestClass::class);

        $this->assertInstanceOf(TestClass::class, $result);
        $this->assertSame('Test', $result->name);
        $this->assertSame(42, $result->value);
    }

    public function test_setEncodeFlags(): void
    {
        $serializer = new JsonSerializer();
        $serializer->setEncodeFlags(JSON_PRETTY_PRINT);

        $data = ['foo' => 'bar'];
        $result = $serializer->serialize($data);

        $this->assertStringContainsString("\n", $result);
    }

    public function test_setDecodeFlags(): void
    {
        $serializer = new JsonSerializer();
        $serializer->setDecodeFlags(JSON_BIGINT_AS_STRING);

        $json = '{"number":12345678901234567890}';
        $result = $serializer->deserialize($json);

        $this->assertIsString($result['number']);
    }

    public function test_prettyPrint(): void
    {
        $this->serializer->prettyPrint();

        $data = ['foo' => 'bar'];
        $result = $this->serializer->serialize($data);

        $this->assertStringContainsString("\n", $result);
        $this->assertStringContainsString('  ', $result);
    }

    public function test_prettyPrint_is_chainable(): void
    {
        $result = $this->serializer->prettyPrint();
        $this->assertSame($this->serializer, $result);
    }

    public function test_setEncodeFlags_is_chainable(): void
    {
        $result = $this->serializer->setEncodeFlags(JSON_PRETTY_PRINT);
        $this->assertSame($this->serializer, $result);
    }

    public function test_setDecodeFlags_is_chainable(): void
    {
        $result = $this->serializer->setDecodeFlags(JSON_BIGINT_AS_STRING);
        $this->assertSame($this->serializer, $result);
    }

    public function test_unicode_handling(): void
    {
        $data = ['message' => 'Hello 世界'];
        $result = $this->serializer->serialize($data);
        $this->assertStringContainsString('世界', $result);
    }

    public function test_slash_handling(): void
    {
        $data = ['url' => 'https://example.com/path'];
        $result = $this->serializer->serialize($data);
        $this->assertStringNotContainsString('\\/', $result);
    }
}

class TestClass
{
    public string $name;
    public int $value;
}
