<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\I18n;

interface Translator
{
    /**
     * Get the translation for the given key.
     */
    public function get(string $key, array $replace = [], ?string $locale = null): string;

    /**
     * Get the current locale.
     */
    public function getLocale(): string;

    /**
     * Set the current locale.
     */
    public function setLocale(string $locale): void;
    
    /**
     * Add a translation directory.
     */
    public function addPath(string $path): void;
}
