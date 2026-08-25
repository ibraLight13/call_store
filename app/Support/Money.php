<?php

namespace App\Support;

use NumberFormatter;

final class Money
{
    /** Formats a minor-unit amount using the current locale's digits. */
    public static function format(?int $minorUnits, string $currency = 'SAR', ?string $locale = null): string
    {
        if ($minorUnits === null) {
            return '';
        }

        $locale ??= app()->getLocale();
        $major = $minorUnits / 100;

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($locale === 'ar' ? 'ar_SA' : $locale, NumberFormatter::DECIMAL);
            $formatted = $formatter->format($major);

            return is_string($formatted) ? $formatted : number_format($major);
        }

        return number_format($major);
    }

    public static function major(int $minorUnits): float
    {
        return $minorUnits / 100;
    }
}
