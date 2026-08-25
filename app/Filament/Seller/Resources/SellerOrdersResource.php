<?php

namespace App\Filament\Seller\Resources;

use App\Domain\Orders\Actions\ShipSellerGroup;
use App\Domain\Orders\Models\OrderSeller;
use App\Filament\Seller\Resources\SellerOrdersResource\Pages as SellerOrderPages;
use App\Models\User;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color as UiColor;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component as LivewireComponent;

final class SellerOrdersResource extends Resource
{
    protected static ?string $model = OrderSeller::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('studio.orders_nav');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('seller_id', Auth::id())
            ->whereHas('order', fn (Builder $q) => $q->whereIn('state', ['paid', 'completed']))
            ->with(['order.buyer']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.id')->label(__('commerce.order'))->weight(FontWeight::Bold)->sortable(),
                TextColumn::make('order.buyer.name')->label(__('studio.client')),
                TextColumn::make('subtotal_minor')
                    ->formatStateUsing(fn (int $state): string => Money::format($state).' SAR'),
                TextColumn::make('shipping_minor')
                    ->formatStateUsing(fn (int $state): string => Money::format($state).' SAR'),
                TextColumn::make('transfer_status')
                    ->badge()
                    ->color(fn (string $state): array => match ($state) {
                        'transferred' => UiColor::Emerald,
                        'failed' => UiColor::Red,
                        'reversed' => UiColor::Gray,
                        default => UiColor::Amber,
                    }),
                TextColumn::make('shipped_at')->dateTime()->placeholder('â€”'),
            ])
            ->recordActions([
                Action::make('ship')
                    ->label(__('commerce.mark_shipped'))
                    ->icon(Heroicon::OutlinedTruck)
                    ->color('primary')
                    ->schema([
                        TextInput::make('tracking_number')->maxLength(80),
                    ])
                    ->visible(fn (OrderSeller $record): bool => $record->shipped_at === null
                        && in_array($record->order->state::getMorphClass(), ['paid', 'completed'], true))
                    ->action(function (LivewireComponent $livewire, OrderSeller $record, array $data): void {
                        $user = Auth::user();

                        if ($user instanceof User) {
                            app(ShipSellerGroup::class)->handle(
                                seller: $user,
                                group: $record,
                                trackingNumber: isset($data['tracking_number']) && is_string($data['tracking_number'])
                                    ? $data['tracking_number']
                                    : null,
                            );
                            $livewire->dispatch('refresh');
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => SellerOrderPages\ListSellerOrders::route('/'),
        ];
    }
}
