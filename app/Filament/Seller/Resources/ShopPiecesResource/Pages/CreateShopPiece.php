<?php

namespace App\Filament\Seller\Resources\ShopPiecesResource\Pages;

use App\Domain\Catalog\Actions\CreatePiece;
use App\Domain\Catalog\Data\PieceData;
use App\Filament\Seller\Resources\ShopPiecesResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class CreateShopPiece extends CreateRecord
{
    protected static string $resource = ShopPiecesResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User|null $seller */
        $seller = auth()->user();

        if (! $seller instanceof User) {
            abort(403);
        }

        $photos = [];

        foreach ((array) ($data['photos'] ?? []) as $photo) {
            if ($photo instanceof UploadedFile && $photo->isValid()) {
                $photos[] = $photo;
            }
        }

        unset($data['photos']);

        return app(CreatePiece::class)->handle($seller, new PieceData(
            text: is_string($data['text'] ?? null) ? (string) $data['text'] : '',
            textLanguage: 'ar',
            transliteration: null,
            scriptId: self::intOrNull($data['script_id'] ?? null),
            mediumId: self::intOrNull($data['medium_id'] ?? null),
            themeId: self::intOrNull($data['theme_id'] ?? null),
            colorId: self::intOrNull($data['color_id'] ?? null),
            widthCm: self::intOrNull($data['width_cm'] ?? null),
            heightCm: self::intOrNull($data['height_cm'] ?? null),
            listingType: ($data['listing_type'] ?? null) === 'commission' ? 'commission' : 'ready',
            priceMinor: self::priceToMinor($data['price'] ?? null),
            currency: is_string($data['currency'] ?? null) ? (string) $data['currency'] : 'SAR',
            uniqueStock: true,
            description: is_string($data['description'] ?? null) ? (string) $data['description'] : null,
            photos: $photos,
        ));
    }

    protected function getRedirectUrl(): string
    {
        return ShopPiecesResource::getUrl('index');
    }

    private static function intOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    private static function priceToMinor(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value * 100;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) round(((float) $value) * 100);
        }

        return null;
    }
}
