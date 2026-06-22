<?php

declare(strict_types=1);

namespace Witals\Framework\Database;

use Witals\Framework\Support\ServiceProvider;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema as ORMSchema;
use Cycle\Annotated;
use Cycle\Schema;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;

abstract class DatabaseServiceProvider extends ServiceProvider
{
    abstract protected function getEntityPaths(): array;

    protected function compileSchema($dbal): array
    {
        $paths = $this->getEntityPaths();

        if (empty($paths)) {
            return [];
        }

        $finder = (new Finder())->files()->in($paths);
        $classLocator = new ClassLocator($finder);

        $schema = (new Schema\Compiler())->compile(new Schema\Registry($dbal), [
            new Schema\Generator\ResetTables(),
            new Annotated\Embeddings($classLocator),
            new Annotated\Entities($classLocator),
            new Annotated\TableInheritance(),
            new Annotated\MergeColumns(),
            new Schema\Generator\GenerateRelations(),
            new Schema\Generator\GenerateTypecast(),
            new Schema\Generator\RenderTables(),
            new Schema\Generator\SyncTables(),
            new Schema\Generator\ValidateEntities(),
        ]);

        return $schema;
    }

    protected function resolveOrm($app, $dbal): ORMInterface
    {
        $cacheFile = $app->basePath('storage/framework/cache/orm_schema.php');
        $env = getenv('APP_ENV') ?: 'production';
        $refresh = (
            $env === 'local' &&
            PHP_SAPI === 'cli' &&
            in_array('--refresh-schema', $_SERVER['argv'] ?? [], true)
        ) || !file_exists($cacheFile);

        if (!$refresh) {
            $schemaArray = require $cacheFile;
        } else {
            $schemaArray = $this->compileSchema($dbal);

            $cacheDir = dirname($cacheFile);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            if (is_writable($cacheDir)) {
                $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($schemaArray, true) . ";\n";
                file_put_contents($cacheFile, $content);
            }
        }

        return new ORM(
            new Factory($dbal),
            new ORMSchema($schemaArray)
        );
    }
}
