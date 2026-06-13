<?php

declare(strict_types=1);

namespace Witals\Framework\Context;

class FileLayoutStorage implements LayoutStorage
{
    public function __construct(
        protected string $storageDir,
        protected string $themeDir,
        protected BlockMarkupParser $parser,
        protected int $maxRevisions = 20,
    ) {}

    public function get(string $type, string $identifier): ?array
    {
        $custom = $this->readFile($this->layoutPath($type, $identifier));

        if ($custom !== null) {
            return $custom;
        }

        return $this->loadThemeTemplate($type, $identifier);
    }

    public function set(string $type, string $identifier, array $blockTree): void
    {
        $path = $this->layoutPath($type, $identifier);
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $isNew = !file_exists($path);
        $this->saveRevision($type, $identifier);

        file_put_contents($path, json_encode($blockTree, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($isNew) {
            $this->saveFirstRevision($type, $identifier, $blockTree);
        }
    }

    protected function saveFirstRevision(string $type, string $identifier, array $blockTree): void
    {
        $revDir = $this->revisionsDir($type, $identifier);
        if (!is_dir($revDir)) {
            mkdir($revDir, 0755, true);
        }

        $themeTree = $this->loadThemeTemplate($type, $identifier);
        $previousContent = $themeTree !== null ? json_encode($themeTree) : '[]';

        file_put_contents($revDir . '/00_theme-default.json', $previousContent);
    }

    public function delete(string $type, string $identifier): void
    {
        $path = $this->layoutPath($type, $identifier);

        if (file_exists($path)) {
            $this->saveRevision($type, $identifier);
            unlink($path);
        }
    }

    public function has(string $type, string $identifier): bool
    {
        return file_exists($this->layoutPath($type, $identifier));
    }

    public function getHierarchyPath(string $type, string $identifier): ?string
    {
        $dir = $this->themeTemplatesDir();
        if ($dir === null || !is_dir($dir)) {
            return null;
        }

        $candidates = $this->buildCandidates($type, $identifier);

        foreach ($candidates as $candidate) {
            $path = $dir . '/' . $candidate . '.html';
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public function getRevisions(string $type, string $identifier): array
    {
        $revDir = $this->revisionsDir($type, $identifier);

        if (!is_dir($revDir)) {
            return [];
        }

        $files = glob($revDir . '/*.json');
        sort($files);

        $revisions = [];
        foreach ($files as $file) {
            $revisions[] = [
                'timestamp' => filemtime($file),
                'file' => basename($file),
                'size' => filesize($file),
            ];
        }

        return $revisions;
    }

    public function getRevision(string $type, string $identifier, string $revisionFile): ?array
    {
        $path = $this->revisionsDir($type, $identifier) . '/' . basename($revisionFile);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        return json_decode($content, true);
    }

    public function restoreRevision(string $type, string $identifier, string $revisionFile): bool
    {
        $tree = $this->getRevision($type, $identifier, $revisionFile);

        if ($tree === null) {
            return false;
        }

        $this->set($type, $identifier, $tree);
        return true;
    }

    protected function loadThemeTemplate(string $type, string $identifier): ?array
    {
        $path = $this->getHierarchyPath($type, $identifier);

        if ($path === null) {
            return null;
        }

        $html = file_get_contents($path);
        if ($html === false) {
            return null;
        }

        return $this->parser->parse($html);
    }

    protected function buildCandidates(string $type, string $identifier): array
    {
        $hierarchy = $this->getTypeHierarchy($type);
        $specific = $type . '-' . $identifier;

        $candidates = [$specific, $type];
        $candidates = array_merge($candidates, $hierarchy);

        return array_unique($candidates);
    }

    protected function getTypeHierarchy(string $type): array
    {
        $map = [
            'page'       => ['singular', 'index'],
            'post'       => ['singular', 'index'],
            'single'     => ['singular', 'index'],
            'home'       => ['index'],
            'front'      => ['home', 'index'],
            'category'   => ['archive', 'index'],
            'tag'        => ['archive', 'index'],
            'taxonomy'   => ['archive', 'index'],
            'date'       => ['archive', 'index'],
            'author'     => ['archive', 'index'],
            'archive'    => ['index'],
            'search'     => ['index'],
            '404'        => ['index'],
            'singular'   => ['index'],
        ];

        return $map[$type] ?? ['index'];
    }

    protected function saveRevision(string $type, string $identifier): void
    {
        $revDir = $this->revisionsDir($type, $identifier);

        if (!is_dir($revDir)) {
            mkdir($revDir, 0755, true);
        }

        $current = $this->layoutPath($type, $identifier);

        if (!file_exists($current)) {
            return;
        }

        $content = file_get_contents($current);
        if ($content === false) {
            return;
        }

        $revFile = $revDir . '/' . date('Ymd_His') . '.json';
        file_put_contents($revFile, $content);

        $this->pruneRevisions($type, $identifier);
    }

    protected function pruneRevisions(string $type, string $identifier): void
    {
        $revDir = $this->revisionsDir($type, $identifier);
        $files = glob($revDir . '/*.json');

        if (count($files) <= $this->maxRevisions) {
            return;
        }

        sort($files);
        $toDelete = array_slice($files, 0, count($files) - $this->maxRevisions);

        foreach ($toDelete as $file) {
            unlink($file);
        }
    }

    protected function layoutPath(string $type, string $identifier): string
    {
        return $this->storageDir . '/contexts/' . $type . '/' . $identifier . '/layout.json';
    }

    protected function revisionsDir(string $type, string $identifier): string
    {
        return $this->storageDir . '/contexts/' . $type . '/' . $identifier . '/revisions';
    }

    protected function themeTemplatesDir(): ?string
    {
        if ($this->themeDir === '' || !is_dir($this->themeDir)) {
            return null;
        }
        return $this->themeDir . '/templates';
    }

    protected function readFile(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }
}
