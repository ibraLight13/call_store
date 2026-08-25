<?php

namespace App\Filament\Seller\Resources\ShopPiecesResource\Pages;

use App\Filament\Seller\Resources\ShopPiecesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListShopPieces extends ListRecords
{
    protected static string $resource = ShopPiecesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('studio.new_piece')),
        ];
    }
}
