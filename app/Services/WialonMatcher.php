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

        // Case like "HOWO RAG 898Q" → normalize
        if (preg_match('/([A-Z]{3})\s*([0-9]{3})([A-Z])/', $upper, $m)) {
            return $m[1] . $m[2] . $m[3]; // RAG898Q
        }

        // Direct pattern AAA999A
        if (preg_match('/([A-Z]{3}[0-9]{3}[A-Z])/', $upper, $m)) {
            return $m[1];
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
