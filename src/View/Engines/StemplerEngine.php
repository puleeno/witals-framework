<?php

declare(strict_types=1);

namespace Witals\Framework\View\Engines;

use Witals\Framework\Contracts\View\Engine;
use Spiral\Stempler\Builder;
use Spiral\Stempler\Loader\DirectoryLoader;
use Spiral\Stempler\Transform\Merge\ExtendsParent;
use Spiral\Stempler\Transform\Merge\ResolveImports;
use Spiral\Stempler\Parser;
use Spiral\Stempler\Lexer;
use Spiral\Stempler\Directive;
use Spiral\Stempler\Compiler\Renderer;

class StemplerEngine implements Engine
{
    protected Builder $builder;
    protected string $cachePath;
    protected array $paths;

    public function __construct(string $cachePath, array $paths = [])
    {
        $this->cachePath = $cachePath;
        $this->paths = $paths;
        $this->initializeBuilder();
    }

    protected function initializeBuilder(): void
    {
        // 1. Setup Parser with required syntaxes
        $parser = new Parser();
        $parser->addSyntax(new Lexer\Grammar\HTMLGrammar(), new Parser\Syntax\HTMLSyntax());
        $parser->addSyntax(new Lexer\Grammar\PHPGrammar(), new Parser\Syntax\PHPSyntax());
        
        $dynamicGrammar = new Lexer\Grammar\DynamicGrammar();
        $parser->addSyntax($dynamicGrammar, new Parser\Syntax\DynamicSyntax());

        // 2. Setup Builder with Loader and Parser
        $baseDir = !empty($this->paths) ? $this->paths[0] : '';
        $this->builder = new Builder(new DirectoryLoader($baseDir, '.stempler.php'), $parser);

        // 3. Setup Directives for DynamicToPHP
        $directives = [
            new Directive\LoopDirective(),
            new Directive\ConditionalDirective(),
        ];

        // 4. Add DynamicToPHP as a FINALIZER
        $this->builder->addVisitor(
            new \Spiral\Stempler\Transform\Finalizer\DynamicToPHP(
                \Spiral\Stempler\Transform\Finalizer\DynamicToPHP::DEFAULT_FILTER,
                $directives
            ),
            Builder::STAGE_FINALIZE
        );

        // 5. Basic Stempler setup for imports and extends
        $this->builder->addVisitor(new ResolveImports($this->builder), Builder::STAGE_PREPARE);
        $this->builder->addVisitor(new ExtendsParent($this->builder), Builder::STAGE_TRANSFORM);

        // 6. Setup Compiler with Renderers
        $compiler = $this->builder->getCompiler();
        $compiler->addRenderer(new Renderer\PHPRenderer());
        $compiler->addRenderer(new Renderer\HTMLRenderer());
    }

    /**
     * Get the evaluated contents of the view at the given path.
     */
    public function get(string $path, array $data = []): string
    {
        $compiledPath = $this->getCompiledPath($path);
        echo "Source: $path\n";
        echo "Compiled: $compiledPath\n";

        if ($this->isExpired($path, $compiledPath)) {
            echo "Compiling...\n";
            $this->compile($path, $compiledPath);
        }

        return $this->evaluatePath($compiledPath, $data);
    }

    protected function getCompiledPath(string $path): string
    {
        return $this->cachePath . '/' . sha1($path) . '.php';
    }

    protected function isExpired(string $path, string $compiledPath): bool
    {
        if (!file_exists($compiledPath)) {
            return true;
        }

        return filemtime($path) > filemtime($compiledPath);
    }

    protected function compile(string $path, string $compiledPath): void
    {
        // Strip extension because DirectoryLoader adds it
        $name = basename($path);
        $name = str_replace(['.stempler.php', '.dark.php'], '', $name);
        
        $result = $this->builder->compile($name);
        
        if (!is_dir(dirname($compiledPath))) {
            mkdir(dirname($compiledPath), 0777, true);
        }

        file_put_contents($compiledPath, $result->getContent());
    }

    protected function evaluatePath(string $__path, array $__data): string
    {
        $obLevel = ob_get_level();
        ob_start();
        extract($__data, EXTR_SKIP);

        try {
            include $__path;
        } catch (\Throwable $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
            throw $e;
        }

        return ltrim(ob_get_clean());
    }
}
