<?php

declare(strict_types=1);

namespace Witals\Framework\Console\Commands;

class MakeBlockCommand extends MakeCommand
{
    protected string $name = 'make:block';
    protected string $description = 'Create a new Gutenberg block renderer';
    protected string $type = 'Block Renderer';
    protected array $arguments = ['name' => 'The name of the block (e.g., core/paragraph)'];
    protected array $options = ['--module' => 'The module to create the block in (default: Gutenberg)'];

    protected string $blockName = '';

    public function handle(array $args): int
    {
        $blockName = $args[0] ?? '';
        if (empty($blockName)) {
            $this->error("Usage: php witals {$this->name} <blockName> [--module=ModuleName]");
            return 1;
        }

        $this->blockName = $blockName;
        return parent::handle($args);
    }

    protected function getStub(): string
    {
        $blockClass = $this->getClassNameFromBlock($this->blockName);
        $slug = $this->getCoreBlockName($this->blockName);

        return <<<PHP
<?php

declare(strict_types=1);

namespace {{ namespace }};

/**
 * {$blockClass} rendering {$this->blockName}
 */
class {{ class }} extends AbstractBlock
{
    public function render(array \$context): string
    {
        \$classes = array_merge(['wp-block-{$slug}'], \$this->classes);
        \$classAttr = !empty(\$classes) ? ' class="' . implode(' ', array_unique(\$classes)) . '"' : '';
        \$styleAttr = !empty(\$this->styles) ? ' style="' . implode(';', \$this->styles) . '"' : '';

        return "<div{\$classAttr}{\$styleAttr}>{\$this->renderInner(\$context)}</div>";
    }
}
PHP;
    }

    protected function getPath(string $name): string
    {
        $module = $this->getModuleName();
        $className = $this->getClassNameFromBlock($this->blockName);
        return $this->app->basePath() . "/framework/presto/modules/{$module}/Renderer/Blocks/{$className}.php";
    }

    protected function getNamespace(string $name): string
    {
        $module = $this->getModuleName();
        return "PrestoWorld\\Modules\\{$module}\\Renderer\\Blocks";
    }

    protected function getClassNameFromBlock(string $name): string
    {
        $parts = explode('/', $name);
        $block = end($parts);
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $block))) . 'Block';
    }

    protected function getCoreBlockName(string $name): string
    {
        if (!str_contains($name, '/')) {
            return $name;
        }
        $parts = explode('/', $name);
        return end($parts);
    }

    protected function getModuleName(): string
    {
        global $argv;
        $module = 'Gutenberg';
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--module=')) {
                $module = substr($arg, 9);
            }
        }
        return $module;
    }
}
