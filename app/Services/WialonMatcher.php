<?php

namespace App\Services;

class WialonMatcher
{
    public static function normalize(?string $s): string
    {
        if ($s === null) return '';
        $s = strtoupper($s);
        $s = preg_replace('/[^A-Z0-9]/', '', $s); // remove spaces & symbols
        return trim($s);
    }

    /**
     * Extract a valid Rwandan plate (AAA999A) from string
     */
    public static function extractPlate(string $name): ?string
    {
        $upper = strtoupper($name);

        if (preg_match('/([A-Z]{2,3})\s*([0-9]{3})\s*([A-Z])/', $upper, $m)) {
            // Reconstruct the plate into the standard format (e.g., RL398Y)
            return $m[1] . $m[2] . $m[3];
        }

        return null;
    }

    public static function isLikelyMatch(string $plate, string $unitName): bool
    {
        $plate = self::normalize($plate);
        $extracted = self::extractPlate($unitName);

        if ($extracted && $extracted === $plate) {
            return true;
        }

        // fallback substring check
        return str_contains(self::normalize($unitName), $plate);
    }
}
