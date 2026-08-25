<?php

namespace App\Domain\Catalog\Data;

use Illuminate\Http\UploadedFile;

final class PieceData
{
    /**
     * @param  array<int, UploadedFile>  $photos
     */
    public function __construct(
        public string $text,
        public string $textLanguage,
        public ?string $transliteration,
        public ?int $scriptId,
        public ?int $mediumId,
        public ?int $themeId,
        public ?int $colorId,
        public ?int $widthCm,
        public ?int $heightCm,
        public string $listingType,
        public ?int $priceMinor,
        public string $currency,
        public bool $uniqueStock,
        public ?string $description,
        public array $photos,
    ) {}
}
