<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

use App\Foundation\Module\ModuleManager;
use App\Foundation\Database\ModuleSchemaManager;
use Throwable;

class SchemaSyncCommand extends Command
{
    protected string $name = 'schema:sync';
    protected string $description = 'Synchronize database schema from modules';

    public function handle(array $args): int
    {
        $this->info("🔄 Synchronizing Module Schemas...\n");

        try {
            $this->app->boot(); // Ensure providers register ModuleSchemaManager
            
            $moduleManager = $this->app->make(ModuleManager::class);
            $schemaManager = $this->app->make(ModuleSchemaManager::class);

            foreach ($moduleManager->allSorted() as $module) {
                if ($module->isEnabled()) {
                    echo "  Checking schema for module: " . $module->getName() . "... ";
                    $synced = $schemaManager->syncModule($module->getPath());
                    if (!empty($synced)) {
                        $this->info("✅ Synced: " . implode(', ', $synced));
                    } else {
                        echo "✓ No changes\n";
                    }
                }
            }

            $this->info("\n✨ Schema synchronization complete!");
            return 0;
        } catch (Throwable $e) {
            $this->error("❌ Error during schema sync: {$e->getMessage()}");
            $this->line("File: {$e->getFile()}:{$e->getLine()}");
            return 1;
        }
    }
}
