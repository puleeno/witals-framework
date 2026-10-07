<?php

declare(strict_types=1);

namespace Witals\Framework\Console\Commands;

use Witals\Framework\Console\Command;
use Witals\Framework\Module\ModulePublisher;

class ModulePublishCommand extends Command
{
    protected string $name = 'module:publish';
    protected string $description = 'Clone module config/ and migrations/ into the project root (config/, database/migrations/)';

    /** @var array<string, string> */
    protected array $options = [
        '--force'      => 'Overwrite existing files in the project root',
        '--config'     => 'Publish only configs',
        '--migrations' => 'Publish only migrations',
    ];

    /**
     * @param list<string> $args
     */
    public function handle(array $args): int
    {
        $options = $this->parseOptions($args);
        $onlyConfig = isset($options['config']);
        $onlyMigrations = isset($options['migrations']);
        $publishConfig = $onlyConfig || !$onlyMigrations;
        $publishMigrations = $onlyMigrations || !$onlyConfig;
        $force = isset($options['force']);

        $publisher = new ModulePublisher($this->app);
        $foundConfig = $publishConfig ? $this->report('config', $publisher->publishConfigs($force)) : false;
        $foundMigrations = $publishMigrations ? $this->report('migrations', $publisher->publishMigrations($force)) : false;

        if (!$foundConfig && !$foundMigrations) {
            $this->comment('Nothing to publish. No module ships config/ or migrations/ directories.');
        } else {
            $this->info('Publish complete. Modules are the source of truth - re-run after editing them.');
        }

        return 0;
    }

    /**
     * @param array{copied: list<string>, overwritten: list<string>, skipped: list<string>} $report
     */
    protected function report(string $kind, array $report): bool
    {
        $hasAnything = $report['copied'] !== [] || $report['overwritten'] !== [] || $report['skipped'] !== [];

        if (!$hasAnything) {
            return false;
        }

        $this->line("[{$kind}]");

        foreach ($report['copied'] as $file) {
            $this->info("  copied      {$file}");
        }

        foreach ($report['overwritten'] as $file) {
            $this->info("  overwritten {$file}");
        }

        foreach ($report['skipped'] as $file) {
            $this->warn("  skipped     {$file} (exists; use --force to overwrite)");
        }

        return true;
    }
}
