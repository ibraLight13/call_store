<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\PieceData;
use App\Domain\Catalog\Media\MediaPipeline;
use App\Domain\Catalog\Models\Piece;
use App\Models\User;
use App\Support\ArabicNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CreatePiece
{
    public function __construct(private readonly MediaPipeline $pipeline) {}

    public function handle(User $seller, PieceData $data): Piece
    {
        Gate::forUser($seller)->authorize('create', Piece::class);

        return DB::transaction(function () use ($seller, $data): Piece {
            $piece = Piece::query()->create([
                'seller_id' => $seller->id,
                'text' => $data->text,
                'normalized_text' => ArabicNormalizer::normalize($data->text),
                'transliteration' => $data->transliteration,
                'text_language' => $data->textLanguage,
                'script_id' => $data->scriptId,
                'medium_id' => $data->mediumId,
                'theme_id' => $data->themeId,
                'color_id' => $data->colorId,
                'width_cm' => $data->widthCm,
                'height_cm' => $data->heightCm,
                'listing_type' => $data->listingType,
                'price_minor' => $data->priceMinor,
                'currency' => $data->currency,
                'unique_stock' => $data->uniqueStock,
                'description' => $data->description,
            ]);

            if ($data->photos !== []) {
                $this->pipeline->attach($piece, $data->photos);
            }

            return $piece;
        });
    }
}
