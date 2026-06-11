<?php

declare(strict_types=1);

namespace Witals\Framework\Module\Console;

use Witals\Framework\Console\Command;

class ModuleValidateCommand extends Command
{
    protected string $name = 'module:validate';
    protected string $description = 'Validate all modules in strict mode';

    public function handle(array $args): int
    {
        $manager = $this->app->make(\Witals\Framework\Module\ModuleManager::class);

        $manager->setStrict(false);
        $manager->discover();

        $errors = $manager->getErrors();
        $allMetadata = $manager->all();

        if ($allMetadata === []) {
            $this->warn('No modules found in "modules/" directory.');

            return 0;
        }

        $this->info(sprintf('Found %d module(s)', count($allMetadata)));
        $this->line('');

        $validator = new \Witals\Framework\Module\ModuleValidator();
        $validator->setAllMetadata($allMetadata);

        $totalErrors = 0;
        $totalWarnings = 0;

        foreach ($allMetadata as $name => $meta) {
            $path = $meta['_path'] ?? '';
            $type = $meta['_type'] ?? 'support';
            $enabled = ($meta['enabled'] ?? false) ? "\033[32menabled\033[0m" : "\033[31mdisabled\033[0m";

            $this->line(sprintf("  \033[33m%s\033[0m (%s) [%s] — %s", $name, $type, $enabled, $path));

            // Schema validation
            $schemaResult = $validator->validate($path, $meta);

            $result = $validator->validateRuntime($name, $meta, $allMetadata);

            $allErrors = array_merge($schemaResult['errors'], $result['errors']);
            $allWarnings = array_merge($schemaResult['warnings'], $result['warnings']);

            if ($allErrors !== []) {
                $totalErrors += count($allErrors);

                foreach ($allErrors as $error) {
                    $this->line("    \033[31m✗ ERROR:\033[0m {$error}");
                }
            }

            if ($allWarnings !== []) {
                $totalWarnings += count($allWarnings);

                foreach ($allWarnings as $warning) {
                    $this->line("    \033[33m⚠ WARNING:\033[0m {$warning}");
                }
            }

            if ($allErrors === [] && $allWarnings === []) {
                $this->line("    \033[32m✓ OK\033[0m");
            }

            $this->line('');
        }

        // Discovery-level errors
        foreach ($errors as $error) {
            $totalErrors++;
            $this->line("  \033[31m✗ ERROR:\033[0m {$error}");
        }

        $this->line('');
        $this->line(str_repeat('-', 60));
        $this->line(sprintf('  %d error(s), %d warning(s)', $totalErrors, $totalWarnings));

        return $totalErrors > 0 ? 1 : 0;
    }
}
