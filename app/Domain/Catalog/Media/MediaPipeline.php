<?php

namespace App\Domain\Catalog\Media;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\Models\PieceMedium;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\Font;

/**
 * Private originals on the local disk; watermarked, responsive derivatives
 * on the public disk (CDN-fronted in production). Never serve originals.
 */
final class MediaPipeline
{
    public const DERIVATIVE_WIDTHS = [480, 960];

    public function __construct(private readonly ImageManager $images) {}

    /**
     * @param  array<int, UploadedFile>  $photos  ordered, first is the cover
     */
    public function attach(Piece $piece, array $photos): void
    {
        foreach ($photos as $index => $photo) {
            if (! $photo->isValid()) {
                continue;
            }

            $originalPath = 'pieces/'.$piece->id.'/'.Str::uuid().'.jpg';
            Storage::disk('local')->put($originalPath, $this->cleanJpeg($photo));

            $media = $piece->media()->create([
                'position' => $index,
                'original_disk' => 'local',
                'original_path' => $originalPath,
                'width' => $this->dimensions($photo)[0],
                'height' => $this->dimensions($photo)[1],
            ]);

            $media->update(['derivatives' => $this->generateDerivatives($piece, $media)]);

            $media->update(['derivatives' => $this->generateDerivatives($piece, $media)]);
        }
    }

    private function cleanJpeg(UploadedFile $photo): string
    {
        return (string) $this->images
            ->decode($photo->getRealPath())
            ->orient()
            ->scaleDown(width: 2048, height: 2048)
            ->encodeUsingFormat(Format::JPEG, quality: 90)
            ->toString();
    }

    /** @return array{0: int, 1: int} */
    private function dimensions(UploadedFile $photo): array
    {
        $image = $this->images->decode($photo->getRealPath());

        return [$image->width(), $image->height()];
    }

    /** @return array<int|string, string> width => public path */
    private function generateDerivatives(Piece $piece, PieceMedium $media): array
    {
        $map = [];

        foreach (self::DERIVATIVE_WIDTHS as $width) {
            $image = $this->images
                ->decode(Storage::disk('local')->path($media->original_path))
                ->scaleDown(width: $width)
                ->orient();

            $this->watermark($image);

            $path = 'pieces/'.$piece->id.'/'.$media->id.'-'.$width.'.jpg';
            Storage::disk('public')->put(
                $path,
                (string) $image->encodeUsingFormat(Format::JPEG, quality: 82)->toString(),
            );

            $map[(string) $width] = $path;
        }

        return $map;
    }

    private function watermark(ImageInterface $image): void
    {
        $fontPath = self::watermarkFontPath();

        if ($fontPath === null) {
            return;
        }

        $size = $image->size();
        $fontSize = max(14, (int) ($size->width() * 0.035));

        $font = (new Font($fontPath, $fontSize))
            ->setColor('ffffff')
            ->setStrokeColor('000000')
            ->setStrokeWidth(2);

        $image->text('KHATT', $size->width() - 16, $size->height() - 14, $font);
    }

    public static function watermarkFontPath(): ?string
    {
        $candidates = [
            'C:\\Windows\\Fonts\\segoeuib.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
