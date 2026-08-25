<?php

namespace App\Filament\Seller\Resources\SellerOrdersResource\Pages;

use App\Filament\Seller\Resources\SellerOrdersResource;
use Filament\Resources\Pages\ListRecords;

final class ListSellerOrders extends ListRecords
{
    protected static string $resource = SellerOrdersResource::class;
}
