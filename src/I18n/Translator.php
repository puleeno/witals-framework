<?php

declare(strict_types=1);

namespace Witals\Framework\I18n;

use Witals\Framework\Contracts\I18n\Translator as TranslatorContract;

class Translator implements TranslatorContract
{
    protected string $locale = 'en';
    protected array $paths = [];
    protected array $loaded = [];

    public function __construct(string $locale = 'en', array $paths = [])
    {
        $this->locale = $locale;
        $this->paths = $paths;
    }

    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?: $this->locale;
        $this->loadLocale($locale);

        $line = $this->loaded[$locale][$key] ?? $key;

        foreach ($replace as $variable => $value) {
            $line = str_replace(':' . $variable, (string)$value, $line);
        }

        return $line;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function addPath(string $path): void
    {
        if (is_dir($path) && !in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }
    }

    protected function loadLocale(string $locale): void
    {
        if (isset($this->loaded[$locale])) {
            return;
        }

        $this->loaded[$locale] = [];

        foreach ($this->paths as $path) {
            // Check for JSON file
            $jsonFile = $path . DIRECTORY_SEPARATOR . $locale . '.json';
            if (file_exists($jsonFile)) {
                $content = json_decode(file_get_contents($jsonFile), true);
                if (is_array($content)) {
                    $this->loaded[$locale] = array_merge($this->loaded[$locale], $content);
                }
            }

            // Check for PHP file
            $phpFile = $path . DIRECTORY_SEPARATOR . $locale . '.php';
            if (file_exists($phpFile)) {
                $content = include $phpFile;
                if (is_array($content)) {
                    $this->loaded[$locale] = array_merge($this->loaded[$locale], $content);
                }
            }
        }
    }
}
