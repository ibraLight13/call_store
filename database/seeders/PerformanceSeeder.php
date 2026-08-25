<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Color;
use App\Domain\Catalog\Models\Medium;
use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\Models\Script;
use App\Domain\Catalog\Models\Theme;
use App\Models\User;
use App\Support\ArabicNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dev-only bulk seeding to validate feed/search performance at scale.
 * Run manually: php artisan db:seed --class=PerformanceSeeder
 */
final class PerformanceSeeder extends Seeder
{
    private const PHRASES = [
        'بِسمِ اللَّهِ الرَّحمٰنِ الرَّحيم', 'الحَمدُ لِلَّه', 'ما شاءَ اللَّه', 'سُبحانَ اللَّه',
        'اللَّهُ أكبر', 'تَوَكَّلتُ عَلى اللَّه', 'الصَّبرُ مِفتاحُ الفَرَج', 'رِزقٌ وَبَرَكة',
        'عِزٌّ وَفَخر', 'حُبٌّ وَسَلام', 'يا رَحمٰن', 'يا رَحيم', 'نورُ العَين',
        'فَنٌّ يُلامِسُ الرّوح', 'خَطٌّ ينبوعُ الجَمال', 'قَلبٌ يَذرُعُ النّور', 'روحٌ تَسمو',
        'مُحَمَّد', 'فاطِمَة', 'عَليٌّ وَزَينَب', 'ريان', 'جنى', 'ملك', 'ليان',
    ];

    public function run(): void
    {
        $sellers = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'seller'))->pluck('id');

        if ($sellers->isEmpty()) {
            $this->command->warn('No sellers found. Seed users first.');

            return;
        }

        $scriptIds = Script::query()->orderBy('sort')->pluck('id');
        $mediumIds = Medium::query()->pluck('id')->all();
        $themeIds = Theme::query()->pluck('id')->all();
        $colorIds = Color::query()->pluck('id')->all();

        $existing = Piece::query()->count();

        if ($existing >= 2000) {
            $this->command->info("Pieces already at {$existing}; skipping.");

            return;
        }

        $target = 2000 - $existing;
        $now = Carbon::now();
        $batch = [];

        for ($i = 0; $i < $target; $i++) {
            $text = self::PHRASES[$i % count(self::PHRASES)];
            $isReady = ($i % 3) !== 2;
            $daysAgo = $i % 400;

            $batch[] = [
                'seller_id' => $sellers[$i % $sellers->count()],
                'text' => $text,
                'normalized_text' => ArabicNormalizer::normalize($text),
                'text_language' => 'ar',
                'script_id' => $scriptIds[$i % $scriptIds->count()],
                'medium_id' => $mediumIds[$i % count($mediumIds)],
                'theme_id' => $themeIds[$i % count($themeIds)],
                'color_id' => $colorIds[$i % count($colorIds)],
                'width_cm' => 30 + ($i % 8) * 10,
                'height_cm' => 40 + ($i % 6) * 10,
                'listing_type' => $isReady ? 'ready' : 'commission',
                'price_minor' => $isReady ? (50 + ($i % 60)) * 1000 : null,
                'currency' => 'SAR',
                'unique_stock' => true,
                'state' => $i % 20 === 19 ? 'pending_review' : 'approved',
                'published_at' => $now->copy()->subDays($daysAgo)->subSeconds($i),
                'reviewed_at' => $now->copy()->subDays($daysAgo),
                'created_at' => $now->copy()->subDays($daysAgo),
                'updated_at' => $now->copy()->subDays($daysAgo),
            ];
        }

        foreach (array_chunk($batch, 250) as $chunk) {
            DB::table('pieces')->insertOrIgnore($chunk);
        }

        $this->command->info("Seeded up to {$target} pieces.");
    }
}
