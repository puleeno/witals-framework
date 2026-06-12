<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Database\Traits;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Database\Traits\Translatable;

class TranslatableTest
{
    use Translatable;

    public function testTranslateReturnsStringWhenNotJson(): void
    {
        $result = $this->translate('plain string');

        $this->assertSame('plain string', $result);
    }

    public function testTranslateReturnsJsonDecodedArray(): void
    {
        $json = '{"en":"Hello","vi":"Xin chào"}';
        $result = $this->translate($json, 'en');

        $this->assertSame('Hello', $result);
    }

    public function testTranslateReturnsLocaleValue(): void
    {
        $data = ['en' => 'Hello', 'vi' => 'Xin chào'];
        $result = $this->translate($data, 'en');

        $this->assertSame('Hello', $result);
    }

    public function testTranslateReturnsDefaultLocaleWhenLocaleNotFound(): void
    {
        $data = ['en' => 'Hello', 'vi' => 'Xin chào'];
        $result = $this->translate($data, 'fr');

        $this->assertSame('Hello', $result);
    }

    public function testTranslateReturnsFirstValueWhenLocaleAndDefaultNotFound(): void
    {
        $data = ['de' => 'Hallo', 'es' => 'Hola'];
        $result = $this->translate($data, 'fr');

        $this->assertSame('Hallo', $result);
    }

    public function testTranslateReturnsFallbackWhenEmpty(): void
    {
        $result = $this->translate([], 'en', 'fallback');

        $this->assertSame('fallback', $result);
    }

    public function testTranslateReturnsFallbackWhenNull(): void
    {
        $result = $this->translate(null, 'en', 'fallback');

        $this->assertSame('fallback', $result);
    }

    public function testTranslateReturnsDataWhenNotArray(): void
    {
        $result = $this->translate(123, 'en', 'fallback');

        $this->assertSame(123, $result);
    }

    public function testToTranslatableJsonEncodesArray(): void
    {
        $translations = ['en' => 'Hello', 'vi' => 'Xin chào'];
        $result = $this->toTranslatableJson($translations);

        $this->assertSame('{"en":"Hello","vi":"Xin chào"}', $result);
    }

    public function testToTranslatableJsonHandlesUnicode(): void
    {
        $translations = ['vi' => 'Xin chào'];
        $result = $this->toTranslatableJson($translations);

        $this->assertStringContainsString('Xin chào', $result);
    }
}
