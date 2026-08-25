<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Medium;
use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\Models\Script;
use App\Domain\Catalog\Models\Theme;
use App\Models\User;
use App\Support\ArabicNormalizer;
use Illuminate\Database\Seeder;

final class DemoPiecesSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();

        if ($sellers->isEmpty()) {
            return;
        }

        $rows = [
            ['بِسمِ اللَّهِ الرَّحمٰنِ الرَّحيم', 'thuluth', 'cotton-paper', 'quranic', 190_000],
            ['الحَمدُ لِلَّه', 'ruqah', 'canvas', 'quotes', null],
            ['وَقُل رَّبِّ زِدني عِلمًا', 'naskh', 'paper-gold', 'quranic', 145_000],
            ['ما شاءَ اللَّه', 'kufic', 'canvas', 'decor', 62_000],
            ['الصَّبرُ مِفتاحُ الفَرَج', 'diwani', 'wood', 'quotes', null],
            ['مُحَمَّد', 'thuluth', 'paper-gold', 'names', 85_000],
            ['سُبحانَ اللَّه', 'nastaliq', 'cotton-paper', 'quotes', null],
            ['لكَ في القَلبِ مَقام', 'diwani', 'cotton-paper', 'quotes', 79_000],
            ['عِزٌّ وَفَخر', 'ruqah', 'canvas', 'names', null],
            ['فَنٌّ يُلامِسُ الرّوح', 'naskh', 'cotton-paper', 'quotes', 55_000],
            ['اللَّهُ نورُ السَّماوات', 'kufic', 'wood', 'quranic', 240_000],
            ['رِزقٌ وَبَرَكة', 'thuluth', 'cotton-paper', 'decor', 96_000],
        ];

        foreach ($rows as $i => [$text, $scriptSlug, $mediumSlug, $themeSlug, $priceMinor]) {
            $seller = $sellers->get($i % $sellers->count());

            if (! $seller instanceof User) {
                continue;
            }

            Piece::query()->updateOrCreate(
                [
                    'seller_id' => $seller->id,
                    'text' => str_replace('ـ', '', $text),
                ],
                [
                    'normalized_text' => ArabicNormalizer::normalize($text),
                    'text_language' => 'ar',
                    'script_id' => Script::query()->where('slug', $scriptSlug)->value('id'),
                    'medium_id' => Medium::query()->where('slug', $mediumSlug)->value('id'),
                    'theme_id' => Theme::query()->where('slug', $themeSlug)->value('id'),
                    'listing_type' => $priceMinor === null ? 'commission' : 'ready',
                    'price_minor' => $priceMinor,
                    'state' => 'approved',
                    'published_at' => now()->subDays($i),
                ],
            );
        }
    }
}
