<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceProvider;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema as ORMSchema;
use Cycle\ORM\SchemaInterface;
use Cycle\Annotated;
use Cycle\Schema;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Query Interceptor
        $this->singleton(\App\Foundation\Database\QueryInterceptor::class, function ($app) {
            return new \App\Foundation\Database\QueryInterceptor($app->make(\App\Foundation\Debug\DebugBar::class));
        });

        // Register Module Schema Manager (declarative schema.json syncer)
        $this->singleton(\App\Foundation\Database\ModuleSchemaManager::class, function ($app) {
            return new \App\Foundation\Database\ModuleSchemaManager(
                $app->make(\Cycle\Database\DatabaseProviderInterface::class)
            );
        });

        // 1. Register Database Manager (DBAL)
        $this->singleton(DatabaseProviderInterface::class, function ($app) {
            $dbConfig = $app->config('database');
            $driver = $dbConfig['default'] ?? env('DB_CONNECTION', 'mysql');
            
            $config = new DatabaseConfig($dbConfig);
            $manager = new DatabaseManager($config);

            // Intercept queries for Statistics/Debug Bar
            if (env('APP_DEBUG_BAR', false) && $app->has(\App\Foundation\Debug\DebugBar::class)) {
                $manager->setLogger($app->make(\App\Foundation\Database\QueryInterceptor::class));
            }

            // Wrap in proxy for lazy connection initialization
            return new \App\Foundation\Database\DatabaseManagerProxy($manager, $driver);
        });

        // 2. Register Database Interface (Default Connection)
        $this->singleton(\Cycle\Database\DatabaseInterface::class, function ($app) {
            return $app->make(DatabaseProviderInterface::class)->database();
        });

        // 3. Register legacy 'wpdb' alias for compatibility
        $this->singleton('wpdb', function ($app) {
            return $app->make(\Cycle\Database\DatabaseInterface::class);
        });

        // 4. Register ORM
        $this->singleton(ORMInterface::class, function ($app) {
            $dbal = $app->make(DatabaseProviderInterface::class);
            
            $cacheFile = $app->basePath('storage/framework/cache/orm_schema.php');
            $refresh = isset($_GET['refresh_schema']) || !file_exists($cacheFile);

            if (!$refresh) {
                $schemaArray = require $cacheFile;
            } else {
                $schemaArray = $this->getSchema($app, $dbal);
                
                // Ensure cache directory exists
                $cacheDir = dirname($cacheFile);
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }

                // Securely save the compiled schema if directory is writable
                if (is_writable($cacheDir)) {
                    $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($schemaArray, true) . ";\n";
                    file_put_contents($cacheFile, $content);
                }
            }

            return new ORM(
                new Factory($dbal),
                new ORMSchema($schemaArray)
            );
        });

        // 3. Register Entity Manager
        $this->singleton(\Cycle\ORM\EntityManagerInterface::class, function ($app) {
            return new \Cycle\ORM\EntityManager($app->make(ORMInterface::class));
        });

        // 4. Register cleanup callback for long-running environments
        $app = $this->app;
        $this->app->terminating(function () use ($app) {
            if ($app->isLongRunning()) {
                $dbal = $app->make(DatabaseProviderInterface::class);
                if ($dbal instanceof \App\Foundation\Database\DatabaseManagerProxy) {
                    $dbal->disconnect();
                }
            }
        });
    }

    public function boot(): void
    {
        // Sync all module schemas declared via schema.json.
        // Uses allSorted() to guarantee FK-referenced tables are created
        // before the tables that reference them (topological dependency order).
        try {
            $app = $this->app;
            if ($app->has(\App\Foundation\Module\ModuleManager::class)) {
                $moduleManager = $app->make(\App\Foundation\Module\ModuleManager::class);
                $schemaManager = $app->make(\App\Foundation\Database\ModuleSchemaManager::class);

                // allSorted() returns modules in topological order (deps first)
                foreach ($moduleManager->allSorted() as $module) {
                    if ($module->isEnabled()) {
                        $synced = $schemaManager->syncModule($module->getPath());
                        if (!empty($synced)) {
                            error_log(sprintf(
                                'SchemaManager: [%s] synced tables: %s',
                                $module->getName(),
                                implode(', ', $synced)
                            ));
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('SchemaManager boot error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    private function getSchema($app, $dbal): array
    {
        // Simple schema compilation on boot (dev mode)
        // For production, this should be cached
        
        $finder = (new Finder())->files()->in([
            $app->basePath('app/Models'),
            $app->basePath('vendor/prestoworld/wp-bridge/src/Sandbox/Models'),
        ]);
        
        // Check if modules have models
        if ($app->has(\App\Foundation\Module\ModuleManager::class)) {
            $modules = $app->make(\App\Foundation\Module\ModuleManager::class)->all();
            foreach ($modules as $module) {
                if ($module->isEnabled() && is_dir($module->getPath() . '/src/Models')) {
                    $finder->in($module->getPath() . '/src/Models');
                }
            }
        }

        $classLocator = new ClassLocator($finder);

        $schema = (new Schema\Compiler())->compile(new Schema\Registry($dbal), [
            new Schema\Generator\ResetTables(),             // Re-declared table schemas (test mode)
            new Annotated\Embeddings($classLocator),        // register embeddable entities
            new Annotated\Entities($classLocator),          // register annotated entities
            new Annotated\TableInheritance(),               // register STI/JTI
            new Annotated\MergeColumns(),                   // add @Table column declarations
            new Schema\Generator\GenerateRelations(),       // generate entity relations
            new Schema\Generator\GenerateTypecast(),        // typecast non-string columns
            new Schema\Generator\RenderTables(),            // declare table schemas
            new Schema\Generator\SyncTables(),              // sync table schemas with database
            new Schema\Generator\ValidateEntities(),        // make sure all entity schemas are correct
        ]);

        return $schema;
    }
}
