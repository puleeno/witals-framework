<?php

declare(strict_types=1);

namespace Witals\Framework\View\Engines;

use Witals\Framework\Contracts\View\Engine;
use Spiral\Stempler\Builder;
use Spiral\Stempler\Parser;
use Spiral\Stempler\Lexer;
use Spiral\Stempler\Directive;
use Spiral\Stempler\Compiler\Renderer;

class StemplerEngine implements Engine
{
    protected Builder $builder;
    protected array $extensions;
    protected array $paths;
    protected string $cachePath;

    public function __construct(string $cachePath, array $paths = [], array $extensions = ['.stempler.php', '.dark.php'])
    {
        $this->cachePath = $cachePath;
        $this->paths = array_filter(array_map(fn($p) => realpath($p) ?: $p, $paths));
        $this->extensions = $extensions;
        $this->initializeBuilder();
    }

    public function addPath(string $path): void
    {
        $realpath = realpath($path) ?: $path;
        if (!in_array($realpath, $this->paths)) {
            $this->paths[] = $realpath;
            $this->initializeBuilder();
        }
    }

    public function prependPath(string $path): void
    {
        $realpath = realpath($path) ?: $path;
        if (!in_array($realpath, $this->paths)) {
            array_unshift($this->paths, $realpath);
            $this->initializeBuilder();
        }
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    protected function initializeBuilder(): void
    {
        // 1. Setup Parser with required syntaxes
        $parser = new Parser();
        $parser->addSyntax(new Lexer\Grammar\HTMLGrammar(), new Parser\Syntax\HTMLSyntax());
        $parser->addSyntax(new Lexer\Grammar\PHPGrammar(), new Parser\Syntax\PHPSyntax());
        $parser->addSyntax(new Lexer\Grammar\InlineGrammar(), new Parser\Syntax\InlineSyntax());
        $parser->addSyntax(new Lexer\Grammar\DynamicGrammar(), new Parser\Syntax\DynamicSyntax());

        // 2. Setup Multi-Path Loader (Aggregate)
        $loader = new class($this->paths, $this->extensions) implements \Spiral\Stempler\Loader\LoaderInterface {
            public function __construct(private array $paths, private array $extensions) {}
            
            public function has(string $name): bool {
                // If absolute path passed (fallback logic)
                if (file_exists($name)) return true;
                
                foreach ($this->paths as $path) {
                    foreach ($this->extensions as $ext) {
                        if (file_exists($path . DIRECTORY_SEPARATOR . $name . $ext)) return true;
                    }
                }
                return false;
            }

            public function load(string $name): \Spiral\Stempler\Loader\Source {
                 // Try relative first
                 foreach ($this->paths as $path) {
                    foreach ($this->extensions as $ext) {
                        $file = $path . DIRECTORY_SEPARATOR . $name . $ext;
                        if (file_exists($file)) {
                            return new \Spiral\Stempler\Loader\Source(file_get_contents($file), $file);
                        }
                    }
                }

                // Fallback: If name looks absolute and exist, use it
                if (file_exists($name)) {
                    return new \Spiral\Stempler\Loader\Source(file_get_contents($name), $name);
                }

                $pathsList = implode('; ', $this->paths);
                throw new \Spiral\Stempler\Exception\LoaderException("Unable to load template \"{$name}\" in any search path. Search paths: [{$pathsList}] with extensions: [" . implode(',', $this->extensions) . "]");
            }
        };

        $this->builder = new Builder($loader, $parser);

        // 3. Setup Directives for DynamicToPHP
        $directives = [
            new \Spiral\Stempler\Directive\LoopDirective(),
            new \Spiral\Stempler\Directive\ConditionalDirective(),
            new \Spiral\Stempler\Directive\PHPDirective(),
            new \Spiral\Stempler\Directive\JsonDirective(),
            new class extends \Spiral\Stempler\Directive\AbstractDirective {
                public function renderInclude(\Spiral\Stempler\Node\Dynamic\Directive $directive): string {
                    $view = $directive->values[0];
                    $data = $directive->values[1] ?? '[]';
                    return sprintf('<?php echo \Witals\Framework\Application::getInstance()->make(\Witals\Framework\Contracts\View\Factory::class)->make(%s, array_merge(get_defined_vars(), %s))->render(); ?>', $view, $data);
                }
            }
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
        $this->builder->addVisitor(new \Spiral\Stempler\Transform\Merge\ResolveImports($this->builder), Builder::STAGE_PREPARE);
        $this->builder->addVisitor(new \Spiral\Stempler\Transform\Merge\ExtendsParent($this->builder), Builder::STAGE_TRANSFORM);
        $this->builder->addVisitor(new \Spiral\Stempler\Transform\Visitor\DefineAttributes(), Builder::STAGE_TRANSFORM);
        $this->builder->addVisitor(new \Spiral\Stempler\Transform\Visitor\DefineStacks(), Builder::STAGE_TRANSFORM);
        $this->builder->addVisitor(new \Spiral\Stempler\Transform\Visitor\DefineBlocks(), Builder::STAGE_TRANSFORM);
        $this->builder->addVisitor(new \Spiral\Stempler\Transform\Visitor\DefineHidden(), Builder::STAGE_FINALIZE);

        // 6. Setup Compiler with Renderers
        $compiler = $this->builder->getCompiler();
        $compiler->addRenderer(new Renderer\CoreRenderer());
        $compiler->addRenderer(new Renderer\PHPRenderer());
        $compiler->addRenderer(new Renderer\HTMLRenderer());
        $compiler->addRenderer(new Renderer\DynamicRenderer());
    }

    public function get(string $path, array $data = []): string
    {
        $compiledPath = $this->getCompiledPath($path);

        if ($this->isExpired($path, $compiledPath)) {
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
        $path = realpath($path) ?: $path;
        $name = $path;

        foreach ($this->paths as $baseDir) {
            if (str_starts_with($path, $baseDir)) {
                $name = trim(substr($path, strlen($baseDir)), DIRECTORY_SEPARATOR);
                break;
            }
        }
        
        // Strip extensions
        foreach ($this->extensions as $ext) {
            if (str_ends_with($name, $ext)) {
                $name = substr($name, 0, -strlen($ext));
                break;
            }
        }
        
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
