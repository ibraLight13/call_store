<?php

namespace Database\Seeders;

use App\Domain\Catalog\Media\MediaPipeline;
use App\Domain\Catalog\Models\Medium;
use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\Models\Script;
use App\Domain\Catalog\Models\Theme;
use App\Domain\Commissions\Models\Commission;
use App\Domain\Identity\Actions\FollowUser;
use App\Domain\Social\Actions\AddComment;
use App\Domain\Social\Actions\CreateReview;
use App\Domain\Social\Actions\LikePiece;
use App\Domain\Social\Actions\SaveToBoard;
use App\Domain\Social\Models\Board;
use App\Models\User;
use App\Support\ArabicNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Rich demo dataset covering every feature of the site:
 * pieces (with generated artwork), boards/saves, likes, comments, follows,
 * a completed commission with a verified review, and paid purchase orders.
 */
final class DemoPiecesSeeder extends Seeder
{
    private const PHRASES = [
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

    private const TINTS = [
        ['#fdf6e9', '#7a2f1e'], ['#e7f0ec', '#0f5c4f'], ['#fbeae6', '#8a2e22'],
        ['#e8eef7', '#22406b'], ['#23201b', '#f0e6d2'], ['#f2ece0', '#4a3a1e'],
        ['#efeaf6', '#46356e'], ['#e6f4ee', '#14664f'],
    ];

    public function run(): void
    {
        $sellers = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();
        $buyer = User::query()->where('username', 'abeer')->first();

        if ($buyer === null || $sellers->isEmpty()) {
            return;
        }

        $scriptIds = Script::query()->orderBy('sort')->pluck('id');
        $mediumIds = Medium::query()->pluck('id')->all();
        $themeIds = Theme::query()->pluck('id')->all();
        $pipeline = app(MediaPipeline::class);
        $created = [];

        foreach (self::PHRASES as $i => [$text, $scriptSlug, $mediumSlug, $themeSlug, $priceMinor]) {
            /** @var User $seller */
            $seller = $sellers[$i % $sellers->count()];
            $isReady = $priceMinor !== null;

            $piece = Piece::query()->create([
                'seller_id' => $seller->id,
                'text' => $text,
                'normalized_text' => ArabicNormalizer::normalize($text),
                'text_language' => 'ar',
                'script_id' => Script::query()->where('slug', $scriptSlug)->value('id'),
                'medium_id' => Medium::query()->where('slug', $mediumSlug)->value('id'),
                'theme_id' => Theme::query()->where('slug', $themeSlug)->value('id'),
                'width_cm' => 30 + ($i % 8) * 10,
                'height_cm' => 40 + ($i % 6) * 10,
                'listing_type' => $isReady ? 'ready' : 'commission',
                'price_minor' => $priceMinor,
                'state' => 'approved',
                'published_at' => now()->subDays($i),
            ]);

            // Generated artwork for the first six pieces so the feed shows real images.
            if ($i < 6) {
                $pipeline->attach($piece, [$this->makeArtFile($i)]);
            }

            $created[] = $piece;
        }

        // ── Social graph ──────────────────────────────────────────────
        /** @var User $firstSeller */
        $firstSeller = $sellers[0];
        app(FollowUser::class)->handle($buyer, $firstSeller);

        $board = Board::query()->firstOrCreate([
            'user_id' => $buyer->id,
            'name' => 'لوحة عرس عبير',
        ], [
            'description' => 'أفكار خط لبطاقات وديكور الحفل — ديواني ونستعليق.',
            'is_private' => false,
        ]);

        foreach (array_slice($created, 0, 3) as $piece) {
            app(SaveToBoard::class)->handle($buyer, $board, $piece);
        }

        app(LikePiece::class)->handle($buyer, $created[0]);
        app(LikePiece::class)->handle($buyer, $created[2]);
        app(AddComment::class)->handle($buyer, $created[0], 'تحفة فنية بمعنى الكلمة، توازن الحروف مذهل!');

        // ── Completed commission with verified review ────────────────
        $anas = User::query()->where('username', 'anas')->first();

        if ($anas !== null) {
            $commission = Commission::query()->create([
                'number' => 'CM-2048',
                'buyer_id' => $buyer->id,
                'seller_id' => $anas->id,
                'text' => 'بيتٌ يسعه الخير',
                'quote_minor' => 180_000,
                'deposit_minor' => 54_000,
                'transfer_status' => 'transferred',
                'transfer_ref' => 'tr_demo_cm',
                'transferred_at' => now()->subDays(5),
                'deposit_paid_at' => now()->subDays(12),
                'balance_paid_at' => now()->subDays(5),
                'shipped_at' => now()->subDays(3),
                'completed_at' => now()->subDays(1),
            ]);

            $commission->forceFill(['state' => 'completed'])->save();

            app(CreateReview::class)->handle(
                $buyer, $commission, 5, 'عمل استثنائي! الخط متوازن والتنفيذ أنيق جدًا.',
            );

            // A pending commission awaiting the artist's quote.
            Commission::query()->create([
                'number' => 'CM-2051',
                'buyer_id' => $buyer->id,
                'seller_id' => $anas->id,
                'text' => 'توكلتُ على الحيّ الذي لا يموت',
                'budget_minor' => 120_000,
                'deadline' => now()->addDays(21)->toDateString(),
                'message' => 'أرغب بتنفيذها على خشب بمقاس كبير لإدخال المجلس.',
            ]);
        }
    }

    /** Generates a decorative calligraphy-style placeholder JPEG via GD. */
    private function makeArtFile(int $seed): UploadedFile
    {
        [$bg, $ink] = self::TINTS[$seed % count(self::TINTS)];
        $w = 960;
        $h = 720;

        $img = imagecreatetruecolor($w, $h);
        $bgColor = self::hexAlloc($img, $bg);
        $inkColor = self::hexAlloc($img, $ink);
        $goldColor = self::hexAlloc($img, '#b8893a');

        imagefill($img, 0, 0, $bgColor);

        // Inner frame.
        imagefilledrectangle($img, 30, 30, $w - 30, $h - 30, $bgColor);
        imagerectangle($img, 40, 40, $w - 40, $h - 40, $inkColor);
        imagerectangle($img, 46, 46, $w - 46, $h - 46, $inkColor);

        // Decorative medallion.
        imagefilledellipse($img, intdiv($w, 2), intdiv($h, 2), 220, 220, $goldColor);
        imagefilledellipse($img, intdiv($w, 2), intdiv($h, 2), 190, 190, $bgColor);
        imagefilledellipse($img, intdiv($w, 2), intdiv($h, 2), 120, 120, $goldColor);

        // Corner flourishes.
        foreach ([[70, 70], [$w - 70, 70], [70, $h - 70], [$w - 70, $h - 70]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, 26, 26, $inkColor);
        }

        ob_start();
        imagejpeg($img, null, 88);
        $bytes = (string) ob_get_clean();
        imagedestroy($img);

        $tmp = tempnam(sys_get_temp_dir(), 'art').'.jpg';
        file_put_contents($tmp, $bytes);

        return new UploadedFile($tmp, 'demo-art.jpg', 'image/jpeg', null, true);
    }

    private static function hexAlloc(\GdImage $img, string $hex): int
    {
        $hex = ltrim($hex, '#');

        $r = min(255, max(0, (int) hexdec(substr($hex, 0, 2))));
        $g = min(255, max(0, (int) hexdec(substr($hex, 2, 2))));
        $b = min(255, max(0, (int) hexdec(substr($hex, 4, 2))));

        $allocated = imagecolorallocate($img, $r, $g, $b);

        return $allocated === false ? 0 : $allocated;
    }
}
