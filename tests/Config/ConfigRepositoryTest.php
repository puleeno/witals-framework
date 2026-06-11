<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Config;

use App\Foundation\Config\ConfigRepository;
use PHPUnit\Framework\TestCase;

class ConfigRepositoryTest extends TestCase
{
    protected string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_config_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->tmpDir);
    }

    protected function rmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    protected function createConfig(string $name, array $data, string $format = 'php'): void
    {
        if ($format === 'php') {
            $content = '<?php return ' . var_export($data, true) . ';';
            file_put_contents($this->tmpDir . '/' . $name . '.php', $content);
        } else {
            file_put_contents($this->tmpDir . '/' . $name . '.json', json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    public function test_default_paths(): void
    {
        $repo = new ConfigRepository();
        $this->assertSame(['config'], $repo->getPaths());
    }

    public function test_custom_paths(): void
    {
        $repo = new ConfigRepository(['path1', 'path2']);
        $this->assertSame(['path1', 'path2'], $repo->getPaths());
    }

    public function test_single_path_as_string(): void
    {
        $repo = new ConfigRepository('custom');
        $this->assertSame(['custom'], $repo->getPaths());
    }

    public function test_set_paths(): void
    {
        $repo = new ConfigRepository('old');
        $repo->setPaths(['new1', 'new2']);
        $this->assertSame(['new1', 'new2'], $repo->getPaths());
    }

    public function test_add_path(): void
    {
        $repo = new ConfigRepository(['a']);
        $repo->addPath('b');
        $this->assertSame(['a', 'b'], $repo->getPaths());
    }

    public function test_has_returns_false_for_unknown_file(): void
    {
        $repo = new ConfigRepository([$this->tmpDir]);
        $this->assertFalse($repo->has('nonexistent'));
    }

    public function test_has_returns_true_for_existing_php_file(): void
    {
        $this->createConfig('app', ['name' => 'Test']);
        $repo = new ConfigRepository([$this->tmpDir]);
        $this->assertTrue($repo->has('app'));
    }

    public function test_has_returns_true_for_existing_json_file(): void
    {
        $this->createConfig('database', ['default' => 'sqlite'], 'json');
        $repo = new ConfigRepository([$this->tmpDir]);
        $this->assertTrue($repo->has('database'));
    }

    public function test_load_php_file(): void
    {
        $this->createConfig('app', ['name' => 'TestApp', 'debug' => true]);
        $repo = new ConfigRepository([$this->tmpDir]);
        $this->assertSame(['name' => 'TestApp', 'debug' => true], $repo->load('app'));
    }

    public function test_load_json_file(): void
    {
        $this->createConfig('database', ['default' => 'mysql', 'host' => 'localhost'], 'json');
        $repo = new ConfigRepository([$this->tmpDir]);
        $this->assertSame(['default' => 'mysql', 'host' => 'localhost'], $repo->load('database'));
    }

    public function test_load_returns_default_for_missing_file(): void
    {
        $repo = new ConfigRepository([$this->tmpDir]);
        $this->assertNull($repo->load('missing'));
        $this->assertSame('fallback', $repo->load('missing', 'fallback'));
    }

    public function test_load_php_preferred_over_json(): void
    {
        $this->createConfig('app', ['format' => 'php'], 'php');
        $this->createConfig('app', ['format' => 'json'], 'json');
        $repo = new ConfigRepository([$this->tmpDir]);
        $this->assertSame(['format' => 'php'], $repo->load('app'));
    }

    public function test_load_caches_result(): void
    {
        $this->createConfig('app', ['value' => 1]);
        $repo = new ConfigRepository([$this->tmpDir]);
        $this->assertSame(['value' => 1], $repo->load('app'));

        $this->createConfig('app', ['value' => 2]);
        $this->assertSame(['value' => 1], $repo->load('app'), 'Should return cached value');
    }

    public function test_clear_cache(): void
    {
        $this->createConfig('app', ['value' => 1]);
        $repo = new ConfigRepository([$this->tmpDir]);
        $repo->load('app');

        $repo->clearCache();
        $this->createConfig('app', ['value' => 2]);
        $this->assertSame(['value' => 2], $repo->load('app'));
    }

    public function test_set_directly(): void
    {
        $repo = new ConfigRepository([$this->tmpDir]);
        $repo->set('custom', ['key' => 'value']);
        $this->assertSame(['key' => 'value'], $repo->load('custom'));
    }

    public function test_set_paths_clears_cache(): void
    {
        $this->createConfig('app', ['value' => 1]);
        $repo = new ConfigRepository([$this->tmpDir]);
        $repo->load('app');

        $repo->setPaths(['/nonexistent']);
        $this->assertNull($repo->load('app'));
    }

    public function test_load_all_collects_all_files(): void
    {
        $this->createConfig('app', ['name' => 'Test']);
        $this->createConfig('database', ['default' => 'sqlite'], 'json');
        $this->createConfig('cache', ['driver' => 'file'], 'php');

        $repo = new ConfigRepository([$this->tmpDir]);
        $all = $repo->loadAll();

        $this->assertArrayHasKey('app', $all);
        $this->assertArrayHasKey('database', $all);
        $this->assertArrayHasKey('cache', $all);
        $this->assertSame(['name' => 'Test'], $all['app']);
    }

    public function test_cache_path_loading(): void
    {
        $this->createConfig('app', ['name' => 'App']);
        $this->createConfig('database', ['default' => 'mysql'], 'json');

        $cacheDir = $this->tmpDir . '/cache';
        mkdir($cacheDir, 0755, true);
        $cacheFile = $cacheDir . '/config.php';

        $repo = new ConfigRepository([$this->tmpDir]);
        $all = $repo->loadAll();

        file_put_contents($cacheFile, '<?php return ' . var_export($all, true) . ';');

        $repo2 = new ConfigRepository(['/nonexistent']);
        $repo2->setCachePath($cacheFile);

        $this->assertTrue($repo2->hasCache());
        $this->assertSame(['name' => 'App'], $repo2->load('app'));
        $this->assertSame(['default' => 'mysql'], $repo2->load('database'));
    }

    public function test_cache_path_fallback_when_missing(): void
    {
        $this->createConfig('app', ['name' => 'App']);
        $repo = new ConfigRepository([$this->tmpDir]);
        $repo->setCachePath('/nonexistent/cache.php');

        $this->assertFalse($repo->hasCache());
        $this->assertSame(['name' => 'App'], $repo->load('app'));
    }

    public function test_multiple_paths_first_wins(): void
    {
        $dirA = $this->tmpDir . '/a';
        $dirB = $this->tmpDir . '/b';
        mkdir($dirA, 0755, true);
        mkdir($dirB, 0755, true);

        $this->createConfigIn($dirA, 'app', ['source' => 'a']);
        $this->createConfigIn($dirB, 'app', ['source' => 'b']);

        $repo = new ConfigRepository([$dirA, $dirB]);
        $this->assertSame(['source' => 'a'], $repo->load('app'));
    }

    protected function createConfigIn(string $dir, string $name, array $data): void
    {
        $content = '<?php return ' . var_export($data, true) . ';';
        file_put_contents($dir . '/' . $name . '.php', $content);
    }
}
