<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Serializer;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Serializer\Serializer;
use Witals\Framework\Serializer\JsonSerializer;
use Witals\Framework\Serializer\ArraySerializer;
use Witals\Framework\Serializer\SerializerInterface;

class SerializerTest extends TestCase
{
    protected Serializer $serializer;
    protected JsonSerializer $jsonSerializer;
    protected ArraySerializer $arraySerializer;

    protected function setUp(): void
    {
        $this->jsonSerializer = new JsonSerializer();
        $this->arraySerializer = new ArraySerializer();
        $this->serializer = new Serializer($this->jsonSerializer, $this->arraySerializer);
    }

    public function test_serialize_with_json_format(): void
    {
        $data = ['foo' => 'bar'];
        $result = $this->serializer->serialize($data, 'json');
        $this->assertSame('{"foo":"bar"}', $result);
    }

    public function test_serialize_with_array_format(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ArraySerializer cannot produce string output. Use JsonSerializer instead.');

        $this->serializer->serialize(['foo' => 'bar'], 'array');
    }

    public function test_deserialize_with_json_format(): void
    {
        $json = '{"foo":"bar"}';
        $result = $this->serializer->deserialize($json, 'json');
        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function test_deserialize_with_array_format(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ArraySerializer cannot parse string input. Use JsonSerializer instead.');

        $this->serializer->deserialize('{"foo":"bar"}', 'array');
    }

    public function test_normalize(): void
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';

        $result = $this->serializer->normalize($obj);
        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function test_denormalize(): void
    {
        $data = ['foo' => 'bar'];
        $result = $this->serializer->denormalize($data, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('bar', $result->foo);
    }

    public function test_registerFormat(): void
    {
        $customSerializer = $this->createMock(SerializerInterface::class);
        $customSerializer->method('serialize')->willReturn('custom');
        $customSerializer->method('deserialize')->willReturn(['custom' => true]);

        $this->serializer->registerFormat('custom', $customSerializer);

        $result = $this->serializer->serialize(['test'], 'custom');
        $this->assertSame('custom', $result);

        $result = $this->serializer->deserialize('test', 'custom');
        $this->assertSame(['custom' => true], $result);
    }

    public function test_serialize_with_unsupported_format_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format: xml');

        $this->serializer->serialize(['foo' => 'bar'], 'xml');
    }

    public function test_deserialize_with_unsupported_format_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format: xml');

        $this->serializer->deserialize('<foo>bar</foo>', 'xml');
    }

    public function test_normalize_delegates_to_jsonSerializer(): void
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';

        $result = $this->serializer->normalize($obj);
        $expected = $this->jsonSerializer->normalize($obj);

        $this->assertSame($expected, $result);
    }

    public function test_denormalize_delegates_to_jsonSerializer(): void
    {
        $data = ['foo' => 'bar'];

        $result = $this->serializer->denormalize($data, \stdClass::class);
        $expected = $this->jsonSerializer->denormalize($data, \stdClass::class);

        $this->assertEquals($expected, $result);
    }

    public function test_default_formats_registered(): void
    {
        $this->assertNotNull($this->serializer->serialize(['test'], 'json'));
        $this->assertNotNull($this->serializer->deserialize('{"test":true}', 'json'));
    }

    public function test_registerFormat_overwrites_existing(): void
    {
        $newJsonSerializer = $this->createMock(SerializerInterface::class);
        $newJsonSerializer->method('serialize')->willReturn('new-json');

        $this->serializer->registerFormat('json', $newJsonSerializer);

        $result = $this->serializer->serialize(['test'], 'json');
        $this->assertSame('new-json', $result);
    }
}
