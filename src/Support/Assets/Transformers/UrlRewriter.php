<?php

declare(strict_types=1);

namespace Witals\Framework\Support\Assets\Transformers;

use Witals\Framework\Support\Assets\Contracts\AssetTransformInterface;

class UrlRewriter implements AssetTransformInterface
{
    protected array $rules = [];

    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    public function transformContent(string $content, string $fromPath, string $toPath): string
    {
        $result = $content;

        foreach ($this->rules as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $result);
        }

        if ($fromPath !== '' && $toPath !== '') {
            $fromUrl = $this->pathToUrl($fromPath);
            $toUrl = $this->pathToUrl($toPath);
            if ($fromUrl !== $toUrl) {
                $result = str_replace($fromUrl, $toUrl, $result);
            }
        }

        return $result;
    }

    public function transformUrl(string $url, string $fromPath, string $toPath): string
    {
        $result = $url;

        foreach ($this->rules as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $result);
        }

        if ($fromPath !== '' && $toPath !== '') {
            $fromUrl = rtrim($this->pathToUrl($fromPath), '/');
            $toUrl = rtrim($this->pathToUrl($toPath), '/');
            if (str_starts_with($result, $fromUrl)) {
                $result = $toUrl . substr($result, strlen($fromUrl));
            }
        }

        return $result;
    }

    public function addRule(string $pattern, string $replacement): void
    {
        $this->rules[$pattern] = $replacement;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    protected function pathToUrl(string $path): string
    {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        if (!preg_match('#^https?://#', $path)) {
            $path = '/' . ltrim($path, '/');
        }
        return $path;
    }
}
