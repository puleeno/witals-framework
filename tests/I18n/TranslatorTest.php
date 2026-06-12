<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\I18n;

use PHPUnit\Framework\TestCase;
use Witals\Framework\I18n\Translator;

class TranslatorTest extends TestCase
{
    private Translator $translator;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/witals_i18n_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->translator = new Translator('en', [$this->tempDir]);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testConstructorSetsLocale(): void
    {
        $translator = new Translator('vi');

        $this->assertSame('vi', $translator->getLocale());
    }

    public function testConstructorSetsDefaultLocale(): void
    {
        $translator = new Translator();

        $this->assertSame('en', $translator->getLocale());
    }

    public function testConstructorSetsPaths(): void
    {
        $translator = new Translator('en', ['/path/to/lang']);

        $this->assertNotEmpty($translator->getPaths());
    }

    public function testGetLocaleReturnsCurrentLocale(): void
    {
        $this->assertSame('en', $this->translator->getLocale());
    }

    public function testSetLocaleChangesLocale(): void
    {
        $this->translator->setLocale('vi');

        $this->assertSame('vi', $this->translator->getLocale());
    }

    public function testAddPathAddsDirectory(): void
    {
        $newPath = sys_get_temp_dir() . '/witals_lang_' . uniqid();
        mkdir($newPath, 0755, true);

        $this->translator->addPath($newPath);

        $this->assertTrue(in_array($newPath, $this->translator->getPaths()));

        rmdir($newPath);
    }

    public function testAddPathIgnoresNonDirectory(): void
    {
        $pathsBefore = $this->translator->getPaths();
        $this->translator->addPath('/non/existent/path');

        $this->assertSame($pathsBefore, $this->translator->getPaths());
    }

    public function testAddPathIgnoresDuplicatePaths(): void
    {
        $pathsBefore = count($this->translator->getPaths());
        $this->translator->addPath($this->tempDir);
        $this->translator->addPath($this->tempDir);

        $this->assertSame($pathsBefore, count($this->translator->getPaths()));
    }

    public function testGetReturnsKeyWhenNotLoaded(): void
    {
        $result = $this->translator->get('welcome.message');

        $this->assertSame('welcome.message', $result);
    }

    public function testGetReturnsKeyWhenNotFound(): void
    {
        $this->createJsonFile('en', ['hello' => 'Hello']);
        $result = $this->translator->get('missing.key');

        $this->assertSame('missing.key', $result);
    }

    public function testGetReturnsTranslationFromJson(): void
    {
        $this->createJsonFile('en', ['welcome' => 'Welcome']);
        $result = $this->translator->get('welcome');

        $this->assertSame('Welcome', $result);
    }

    public function testGetReturnsTranslationFromPhp(): void
    {
        $this->createPhpFile('en', ['welcome' => 'Welcome']);
        $result = $this->translator->get('welcome');

        $this->assertSame('Welcome', $result);
    }

    public function testGetReplacesPlaceholders(): void
    {
        $this->createJsonFile('en', ['greeting' => 'Hello :name']);
        $result = $this->translator->get('greeting', ['name' => 'John']);

        $this->assertSame('Hello John', $result);
    }

    public function testGetReplacesMultiplePlaceholders(): void
    {
        $this->createJsonFile('en', ['message' => ':greeting, :name!']);
        $result = $this->translator->get('message', ['greeting' => 'Hello', 'name' => 'John']);

        $this->assertSame('Hello, John!', $result);
    }

    public function testGetWithSpecificLocale(): void
    {
        $this->createJsonFile('en', ['welcome' => 'Welcome']);
        $this->createJsonFile('vi', ['welcome' => 'Xin chào']);

        $result = $this->translator->get('welcome', [], 'vi');

        $this->assertSame('Xin chào', $result);
    }

    public function testGetWithSpecificLocaleDoesNotChangeDefault(): void
    {
        $this->createJsonFile('en', ['welcome' => 'Welcome']);
        $this->createJsonFile('vi', ['welcome' => 'Xin chào']);

        $this->translator->get('welcome', [], 'vi');

        $this->assertSame('en', $this->translator->getLocale());
    }

    public function testGetLoadsLocaleOnce(): void
    {
        $this->createJsonFile('en', ['welcome' => 'Welcome']);

        $this->translator->get('welcome');
        $this->translator->get('welcome');

        $this->expectNotToPerformAssertions();
    }

    public function testGetMergesMultipleFiles(): void
    {
        $this->createJsonFile('en', ['key1' => 'Value 1']);
        $this->createPhpFile('en', ['key2' => 'Value 2']);

        $result1 = $this->translator->get('key1');
        $result2 = $this->translator->get('key2');

        $this->assertSame('Value 1', $result1);
        $this->assertSame('Value 2', $result2);
    }

    public function testGetHandlesNestedKeys(): void
    {
        $this->createJsonFile('en', ['messages' => ['welcome' => 'Welcome']]);

        $result = $this->translator->get('messages.welcome');

        $this->assertSame('Welcome', $result);
    }

    public function testGetHandlesNumericReplacement(): void
    {
        $this->createJsonFile('en', ['count' => 'Count: :number']);
        $result = $this->translator->get('count', ['number' => 42]);

        $this->assertSame('Count: 42', $result);
    }

    public function testGetHandlesNullReplacement(): void
    {
        $this->createJsonFile('en', ['message' => 'Value: :value']);
        $result = $this->translator->get('message', ['value' => null]);

        $this->assertSame('Value: ', $result);
    }

    public function testLoadLocaleHandlesInvalidJson(): void
    {
        file_put_contents($this->tempDir . '/en.json', 'invalid json');

        $result = $this->translator->get('any.key');

        $this->assertSame('any.key', $result);
    }

    public function testLoadLocaleHandlesNonArrayPhpFile(): void
    {
        file_put_contents($this->tempDir . '/en.php', '<?php return "string";');

        $result = $this->translator->get('any.key');

        $this->assertSame('any.key', $result);
    }

    public function testMultipleLocalesCanBeLoaded(): void
    {
        $this->createJsonFile('en', ['welcome' => 'Welcome']);
        $this->createJsonFile('vi', ['welcome' => 'Xin chào']);

        $resultEn = $this->translator->get('welcome', [], 'en');
        $resultVi = $this->translator->get('welcome', [], 'vi');

        $this->assertSame('Welcome', $resultEn);
        $this->assertSame('Xin chào', $resultVi);
    }

    private function createJsonFile(string $locale, array $data): void
    {
        file_put_contents($this->tempDir . '/' . $locale . '.json', json_encode($data));
    }

    private function createPhpFile(string $locale, array $data): void
    {
        file_put_contents($this->tempDir . '/' . $locale . '.php', '<?php return ' . var_export($data, true) . ';');
    }
}
