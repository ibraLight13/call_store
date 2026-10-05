<?php

namespace App\Support;

use App\Domain\Catalog\Models\Piece;

/**
 * Flattens a Piece into the plain array the gallery card renders.
 * Shared by the Livewire feed (which must not hold models in its payload) and Blade includes.
 * Expects seller, script and media to be eager-loaded.
 */
final class PinPresenter
{
    private const ASPECTS = ['3 / 4', '1 / 1', '4 / 3'];

    /** @return array<string, string|bool|int|null> */
    public static function present(Piece $piece, int $index): array
    {
        [$bg, $ink] = Palette::tint($index);

        $script = $piece->script;
        $scriptSlug = $script !== null ? $script->slug : 'naskh';
        $scriptName = '';
        if ($script !== null) {
            $localized = $script->{'name_'.app()->getLocale()};
            $scriptName = is_string($localized) ? $localized : '';
        }

        $pin = [
            'id' => $piece->id,
            'url' => route('pieces.show', $piece),
            'text' => $piece->text,
            'img' => $piece->coverMedia()?->derivativeUrl(480),
            'bg' => $bg,
            'ink' => $ink,
            'font' => Palette::font($scriptSlug),
            'weight' => Palette::weight($scriptSlug),
            'size' => max(22, min(54, (int) (220 / max(6, mb_strlen($piece->text)) + 14))),
            'aspect' => self::ASPECTS[$piece->id % count(self::ASPECTS)],
            'scriptName' => $scriptName,
            'sellerUrl' => $piece->seller->profileUrl(),
            'sellerName' => $piece->seller->name,
            'isReady' => $piece->isReady() && $piece->price_minor !== null,
            'sold' => $piece->sold_at !== null,
            'price' => Money::format($piece->price_minor),
        ];

        ksort($pin);

        return $pin;
    }
}
