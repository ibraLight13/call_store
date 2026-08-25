<?php

namespace App\Filament\Seller\Resources\ShopPiecesResource\Pages;

use App\Domain\Catalog\Models\Piece;
use App\Filament\Seller\Resources\ShopPiecesResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditShopPiece extends EditRecord
{
    protected static string $resource = ShopPiecesResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['price']) && is_numeric($data['price'])) {
            $data['price_minor'] = (int) round(((float) $data['price']) * 100);
        }

        unset($data['photos']);

        return $data;
    }

    public function resolveRecord(int|string $key): Model
    {
        $query = ShopPiecesResource::getEloquentQuery();
        $piece = $query->whereKey($key)->first();

        if (! $piece instanceof Piece) {
            abort(404);
        }

        return $piece;
    }
}
