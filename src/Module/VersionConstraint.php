<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

class VersionConstraint
{
    public static function satisfies(string $version, string $constraint): bool
    {
        $constraint = trim($constraint);

        if ($constraint === '*' || $constraint === '') {
            return true;
        }

        // Handle || (OR) constraints
        if (str_contains($constraint, '||')) {
            foreach (explode('||', $constraint) as $part) {
                if (self::satisfies($version, trim($part))) {
                    return true;
                }
            }
            return false;
        }

        // Handle multi-constraint (space-separated, AND)
        if (preg_match('/^(>=|<=|!=|>|<|=)?\s*[\d.*]+\s+(>=|<=|!=|>|<|=)\s*/', $constraint)) {
            $parts = preg_split('/\s+/', $constraint);
            foreach ($parts as $part) {
                if (!self::satisfiesSingle($version, $part)) {
                    return false;
                }
            }
            return true;
        }

        return self::satisfiesSingle($version, $constraint);
    }

    protected static function satisfiesSingle(string $version, string $constraint): bool
    {
        $constraint = trim($constraint);

        // Wildcard: 1.0.*
        if (str_contains($constraint, '*')) {
            $pattern = '/^' . str_replace(['.', '*'], ['\\.', '.*'], $constraint) . '$/';
            return (bool) preg_match($pattern, $version);
        }

        // Exact match (no operator)
        if (preg_match('/^(\d+\.\d+\.\d+)$/', $constraint)) {
            return $version === $constraint;
        }

        // Operator prefix: ^, ~, >=, <=, !=, >, <, =
        $operator = '';
        $targetVersion = $constraint;

        if (preg_match('/^([\^~><=!]+)\s*(.+)$/', $constraint, $m)) {
            $operator = $m[1];
            $targetVersion = trim($m[2]);
        }

        // Validate target version format
        if (!preg_match('/^\d+\.\d+\.\d+$/', $targetVersion)) {
            return true;
        }

        [$major, $minor, $patch] = array_map('intval', explode('.', $targetVersion));
        [$vMajor, $vMinor, $vPatch] = array_map('intval', explode('.', $version));

        return match ($operator) {
            '^' => self::satisfiesCaret($vMajor, $vMinor, $vPatch, $major, $minor, $patch),
            '~' => self::satisfiesTilde($vMajor, $vMinor, $vPatch, $major, $minor, $patch),
            '>=' => self::compareVersions($vMajor, $vMinor, $vPatch, $major, $minor, $patch) >= 0,
            '<=' => self::compareVersions($vMajor, $vMinor, $vPatch, $major, $minor, $patch) <= 0,
            '>' => self::compareVersions($vMajor, $vMinor, $vPatch, $major, $minor, $patch) > 0,
            '<' => self::compareVersions($vMajor, $vMinor, $vPatch, $major, $minor, $patch) < 0,
            '!=' => self::compareVersions($vMajor, $vMinor, $vPatch, $major, $minor, $patch) !== 0,
            '=' => self::compareVersions($vMajor, $vMinor, $vPatch, $major, $minor, $patch) === 0,
            default => true,
        };
    }

    protected static function satisfiesCaret(int $vMajor, int $vMinor, int $vPatch, int $major, int $minor, int $patch): bool
    {
        // ^1.0.0: >=1.0.0, <2.0.0
        // ^0.1.0: >=0.1.0, <0.2.0
        if ($major !== 0) {
            $nextMajor = $major + 1;
            return self::compareVersions($vMajor, $vMinor, $vPatch, $major, $minor, $patch) >= 0
                && self::compareVersions($vMajor, $vMinor, $vPatch, $nextMajor, 0, 0) < 0;
        }

        // ^0.0.1: >=0.0.1, <0.0.2
        if ($minor !== 0) {
            $nextMinor = $minor + 1;
            return self::compareVersions($vMajor, $vMinor, $vPatch, $major, $minor, $patch) >= 0
                && self::compareVersions($vMajor, $vMinor, $vPatch, 0, $nextMinor, 0) < 0;
        }

        // ^0.0.1: >=0.0.1, <0.0.2
        $nextPatch = $patch + 1;
        return self::compareVersions($vMajor, $vMinor, $vPatch, $major, $minor, $patch) >= 0
            && self::compareVersions($vMajor, $vMinor, $vPatch, 0, 0, $nextPatch) < 0;
    }

    protected static function satisfiesTilde(int $vMajor, int $vMinor, int $vPatch, int $major, int $minor, int $patch): bool
    {
        // ~1.0.0: >=1.0.0, <1.1.0
        $nextMinor = $minor + 1;
        return self::compareVersions($vMajor, $vMinor, $vPatch, $major, $minor, $patch) >= 0
            && self::compareVersions($vMajor, $vMinor, $vPatch, $major, $nextMinor, 0) < 0;
    }

    protected static function compareVersions(int $aMajor, int $aMinor, int $aPatch, int $bMajor, int $bMinor, int $bPatch): int
    {
        if ($aMajor !== $bMajor) {
            return $aMajor <=> $bMajor;
        }
        if ($aMinor !== $bMinor) {
            return $aMinor <=> $bMinor;
        }
        return $aPatch <=> $bPatch;
    }
}
