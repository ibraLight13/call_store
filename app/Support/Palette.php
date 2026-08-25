<?php

namespace App\Support;

final class Palette
{
    private const FALLBACK_TINT = ['#fdf6e9', '#1c1a17'];

    /** @return array{0: string, 1: string} [background, ink] for a grid index */
    public static function tint(int $index): array
    {
        $tints = config('catalog.paper_tints');

        if (! is_array($tints) || $tints === []) {
            return self::FALLBACK_TINT;
        }

        return self::validTint($tints[$index % count($tints)]) ?? self::FALLBACK_TINT;
    }

    public static function font(string $scriptSlug): string
    {
        $fonts = config('catalog.script_fonts');

        if (is_array($fonts)) {
            $font = $fonts[$scriptSlug] ?? null;

            if (is_string($font) && $font !== '') {
                return $font;
            }
        }

        return "'Amiri', serif";
    }

    public static function weight(string $scriptSlug): int
    {
        $weights = config('catalog.script_weights');

        if (is_array($weights)) {
            $weight = $weights[$scriptSlug] ?? null;

            if (is_int($weight)) {
                return $weight;
            }
        }

        return 400;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function validTint(mixed $pair): ?array
    {
        if (is_array($pair) && isset($pair[0], $pair[1]) && is_string($pair[0]) && is_string($pair[1])) {
            return [$pair[0], $pair[1]];
        }

        return null;
    }
}
