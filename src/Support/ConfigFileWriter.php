<?php

declare(strict_types=1);

namespace Witals\Framework\Support;

class ConfigFileWriter
{
    public static function write(string $path, array $config): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . self::export($config, 0) . ";\n";

        $tempPath = $path . '.tmp';
        file_put_contents($tempPath, $content, LOCK_EX);
        rename($tempPath, $path);
    }

    protected static function export(mixed $value, int $depth): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (is_nan($value)) {
                return 'NAN';
            }
            if (is_infinite($value)) {
                return $value > 0 ? 'INF' : '-INF';
            }
            return (string) $value;
        }

        if (is_string($value)) {
            return var_export($value, true);
        }

        if (is_array($value)) {
            return self::exportArray($value, $depth);
        }

        return var_export($value, true);
    }

    protected static function exportArray(array $array, int $depth): string
    {
        if ($array === []) {
            return '[]';
        }

        $isList = array_is_list($array);
        $indent = str_repeat('    ', $depth + 1);

        $parts = [];
        foreach ($array as $key => $value) {
            $exported = self::export($value, $depth + 1);
            if ($isList) {
                $parts[] = $indent . $exported;
            } else {
                $parts[] = $indent . self::export($key, $depth + 1) . ' => ' . $exported;
            }
        }

        $glue = ",\n";
        $body = implode($glue, $parts);

        if ($depth === 0) {
            return "[\n{$body},\n]";
        }

        return "[\n{$body},\n" . str_repeat('    ', $depth) . ']';
    }
}
