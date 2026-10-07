<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Application;

/**
 * Clones module-owned assets into the project root.
 *
 * Modules keep their source of truth in their own directory:
 *   framework/presto/modules/<Module>/config/*.php       → <root>/config/
 *   framework/presto/modules/<Module>/migrations/*.php   → <root>/database/migrations/
 *
 * Published copies are what the runtime loads (ConfigRepository reads <root>/config,
 * SchemaMigrationManager reads <root>/database/migrations), so publishing is a plain file clone.
 */
class ModulePublisher
{
    public const KIND_CONFIG = 'config';
    public const KIND_MIGRATION = 'migrations';

    /** @var list<string> */
    private array $modulePaths;

    /**
     * @param list<string> $modulePaths Module directories to scan; defaults to the
     *                                  standard discovery paths.
     */
    public function __construct(
        private Application $app,
        array $modulePaths = [],
    ) {
        $this->modulePaths = $modulePaths !== [] ? array_values($modulePaths) : [
            $app->basePath('modules'),
            $app->basePath('framework/witals/modules'),
            $app->basePath('framework/presto/modules'),
        ];
    }

    /**
     * @return list<string> The module paths this publisher scans.
     */
    public function modulePaths(): array
    {
        return $this->modulePaths;
    }

    /**
     * Module directories that actually contain the given source directory.
     *
     * @return list<string>
     */
    public function sources(string $kind): array
    {
        $sources = [];

        foreach ($this->modulePaths as $modulesPath) {
            if (!is_dir($modulesPath)) {
                continue;
            }

            foreach ($this->directories($modulesPath) as $modulePath) {
                if (is_dir($modulePath . '/' . $kind)) {
                    $sources[] = $modulePath;
                }
            }
        }

        return $sources;
    }

    /**
     * Clone one kind of asset (config|migrations) from every module into the project root.
     *
     * @return array{copied: list<string>, overwritten: list<string>, skipped: list<string>}
     *         Entries are "<module-name>/<file>".
     */
    public function publish(string $kind, bool $force = false): array
    {
        $target = $this->targetPath($kind);
        $report = ['copied' => [], 'overwritten' => [], 'skipped' => []];

        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        foreach ($this->sources($kind) as $modulePath) {
            $files = glob($modulePath . '/' . $kind . '/*.php') ?: [];
            sort($files);

            foreach ($files as $file) {
                $name = basename($file);
                $destination = $target . '/' . $name;
                $label = basename($modulePath) . '/' . $name;

                if (!file_exists($destination)) {
                    copy($file, $destination);
                    $report['copied'][] = $label;

                    continue;
                }

                if (!$force) {
                    $report['skipped'][] = $label;

                    continue;
                }

                copy($file, $destination);
                $report['overwritten'][] = $label;
            }
        }

        return $report;
    }

    /**
     * @return array{copied: list<string>, overwritten: list<string>, skipped: list<string>}
     */
    public function publishConfigs(bool $force = false): array
    {
        return $this->publish(self::KIND_CONFIG, $force);
    }

    /**
     * @return array{copied: list<string>, overwritten: list<string>, skipped: list<string>}
     */
    public function publishMigrations(bool $force = false): array
    {
        return $this->publish(self::KIND_MIGRATION, $force);
    }

    /**
     * Publish configs and migrations in one pass.
     *
     * @return array{config: array{copied: list<string>, overwritten: list<string>, skipped: list<string>}, migrations: array{copied: list<string>, overwritten: list<string>, skipped: list<string>}}
     */
    public function publishAll(bool $force = false): array
    {
        return [
            'config' => $this->publish(self::KIND_CONFIG, $force),
            'migrations' => $this->publish(self::KIND_MIGRATION, $force),
        ];
    }

    private function targetPath(string $kind): string
    {
        return match ($kind) {
            self::KIND_CONFIG => $this->app->basePath('config'),
            self::KIND_MIGRATION => $this->app->basePath('database/migrations'),
            default => throw new \InvalidArgumentException("Unknown publish kind: {$kind}"),
        };
    }

    /**
     * @return list<string>
     */
    private function directories(string $parent): array
    {
        $entries = scandir($parent);
        if ($entries === false) {
            return [];
        }

        $directories = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir($parent . '/' . $entry)) {
                $directories[] = $parent . '/' . $entry;
            }
        }

        return $directories;
    }
}
