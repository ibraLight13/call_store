<?php

namespace App\Support;

final class ArabicNormalizer
{
    /** Diacritics (harakat), superscript alef, and tatweel. */
    private const STRIP = '/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u';

    public static function normalize(string $text): string
    {
        $stripped = preg_replace(self::STRIP, '', $text);
        $text = is_string($stripped) ? $stripped : $text;

        // Unify alef/hamza carriers, yeh variants, teh marbuta.
        $map = [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ى' => 'ي',
            'ة' => 'ه',
            'ؤ' => 'و',
            'ئ' => 'ي',
            // Arabic-Indic digits to Latin for consistent matching.
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $text = strtr($text, $map);

        // Presentation forms to their base letters (NFKC decomposition).
        if (class_exists(\Normalizer::class)) {
            $decomposed = \Normalizer::normalize($text, \Normalizer::FORM_KD);

            if (is_string($decomposed)) {
                $cleaned = preg_replace('/[\x{FE70}-\x{FEFF}\x{064B}-\x{065F}]/u', '', $decomposed);
                $text = is_string($cleaned) ? $cleaned : $decomposed;
            }
        }

        $lowered = mb_strtolower($text);
        $collapsed = preg_replace('/[\s\x{00A0}]+/u', ' ', $lowered);

        return trim(is_string($collapsed) ? $collapsed : $lowered);
    }
}
