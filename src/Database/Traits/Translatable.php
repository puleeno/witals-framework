<?php

declare(strict_types=1);

namespace Witals\Framework\Database\Traits;

/**
 * Trait Translatable
 * 
 * Provides utility for handling JSON translatable fields.
 * Assumes translatable fields are JSON columns in the database
 * or properties containing a locale => value map.
 */
trait Translatable
{
    /**
     * Get the translated value for a field.
     * 
     * @param mixed $data The raw row data or property value
     * @param string|null $locale 
     * @param mixed $fallback
     * @return mixed
     */
    public function translate(mixed $data, ?string $locale = null, mixed $fallback = null)
    {
        $locale = $locale ?: app()->translator()->getLocale();
        $defaultLocale = config('app.locale', 'en');

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            } else {
                return $data; // Not JSON, return as is (fallback)
            }
        }

        if (!is_array($data)) {
            return $data ?: $fallback;
        }

        return $data[$locale] ?? $data[$defaultLocale] ?? reset($data) ?? $fallback;
    }

    /**
     * Format a value for storage as JSON.
     * 
     * @param array $translations locale => value map
     * @return string
     */
    public function toTranslatableJson(array $translations): string
    {
        return json_encode($translations, JSON_UNESCAPED_UNICODE);
    }
}
