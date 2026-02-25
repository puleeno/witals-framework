<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Witals\Framework\Console\Command;
use PrestoWorld\Bridge\WordPress\ZeroMigration\WordPressZeroMigration;
use Throwable;

class WordPressZeroMigrateCommand extends Command
{
    protected string $name = 'wordpress:zero-migrate';
    protected string $description = 'Migrate WordPress to PrestoWorld';
    protected array $options = [
        '--dry-run, -d' => 'Check what would be migrated without making any changes',
        '--force, -f' => 'Force migration even if some checks fail',
    ];

    public function handle(array $args): int
    {
        $dryRun = $this->hasOption($args, 'dry-run', 'd');
        $force = $this->hasOption($args, 'force', 'f');
        
        try {
            $migration = new WordPressZeroMigration();
            
            $this->info("🚀 PrestoWorld Zero Migration\n");
            
            // Detect WordPress installation
            if (!$migration->detectWordPress()) {
                $this->error("❌ WordPress installation not detected\n");
                $this->line("Required files:");
                $this->line("  - wp-config.php (in project root)");
                $this->line("  - wp-content/ (with themes/ and plugins/ subdirectories)");
                return 1;
            }
            
            $this->info("✅ WordPress installation detected");
            
            // Parse wp-config.php
            $wpConfig = $migration->parseWpConfig();
            if (empty($wpConfig)) {
                $this->error("❌ Failed to parse wp-config.php");
                return 1;
            }
            
            $this->info("✅ wp-config.php parsed successfully\n");
            $this->line("Database Configuration:");
            foreach ($wpConfig as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
            
            if ($dryRun) {
                $this->info("\n🔍 Dry run mode - no changes made");
                return 0;
            }
            
            // Validate database
            $this->info("\n🔍 Validating WordPress database...");
            $validation = $migration->validateDatabase();
            
            if (isset($validation['error'])) {
                $this->error("❌ Database error: {$validation['error']}");
                return 1;
            }
            
            if (!$validation['valid']) {
                $this->error("❌ WordPress database validation failed");
                $this->line("Missing tables:");
                foreach ($validation['missing_tables'] as $table) {
                    $this->line("  - {$table}");
                }
                
                if (!$force) {
                    $this->line("\nUse --force to continue anyway");
                    return 1;
                }
            }
            
            $this->info("✅ WordPress database validated");
            
            // Get site info
            $siteInfo = $migration->getSiteInfo();
            if (!isset($siteInfo['error'])) {
                $this->line("\n📋 Site Information:");
                $this->line("  Site URL: {$siteInfo['siteurl']}");
                $this->line("  Blog Name: {$siteInfo['blogname']}");
                $this->line("  Active Theme: {$siteInfo['active_theme']}");
                $this->line("  Posts: {$siteInfo['post_count']}");
                $this->line("  Table Prefix: {$siteInfo['table_prefix']}");
            }
            
            // Execute migration
            $this->info("\n🔄 Executing zero migration...");
            $result = $migration->migrate();
            
            if (!$result['success']) {
                $this->error("❌ Migration failed: {$result['message']}");
                return 1;
            }
            
            $this->info("✅ Zero migration completed successfully!\n");
            $this->info("🎉 Your WordPress site is now ready for PrestoWorld!\n");
            $this->line("Next steps:");
            $this->line("  1. Start the server: php witals serve");
            $this->line("  2. Visit your site");
            $this->line("  3. Test theme and plugins");
            
            return 0;
        } catch (Throwable $e) {
            $this->error("❌ Migration error: {$e->getMessage()}");
            return 1;
        }
    }
}
