<?php

namespace App\Filament\Seller\Resources;

use App\Domain\Catalog\Actions\PublishPiece;
use App\Domain\Catalog\Models\Color;
use App\Domain\Catalog\Models\Medium;
use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\Models\Script;
use App\Domain\Catalog\Models\Theme;
use App\Filament\Seller\Resources\ShopPiecesResource\Pages as ShopPiecesPages;
use App\Models\User;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color as UiColor;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component as LivewireComponent;

final class ShopPiecesResource extends Resource
{
    protected static ?string $model = Piece::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('studio.pieces_nav');
    }

    /**
     * Sellers only ever see their own catalogue.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('seller_id', Auth::id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('text')
                ->label(__('catalog.text'))
                ->required()
                ->minLength(2)
                ->maxLength(500)
                ->columnSpanFull(),
            Select::make('script_id')
                ->label(__('catalog.script'))
                ->options(fn (): Collection => Script::query()->orderBy('sort')->pluck('name_'.app()->getLocale(), 'id'))
                ->searchable(),
            Select::make('medium_id')
                ->label(__('catalog.medium'))
                ->options(fn (): Collection => Medium::query()->orderBy('sort')->pluck('name_'.app()->getLocale(), 'id'))
                ->searchable(),
            Select::make('theme_id')
                ->label(__('catalog.theme'))
                ->options(fn (): Collection => Theme::query()->orderBy('sort')->pluck('name_'.app()->getLocale(), 'id'))
                ->searchable(),
            Select::make('color_id')
                ->label(__('catalog.color'))
                ->options(fn (): Collection => Color::query()->orderBy('sort')->pluck('name_'.app()->getLocale(), 'id'))
                ->searchable(),
            TextInput::make('width_cm')->label(__('catalog.width'))->numeric()->minValue(5)->maxValue(500),
            TextInput::make('height_cm')->label(__('catalog.height'))->numeric()->minValue(5)->maxValue(500),
            Radio::make('listing_type')
                ->label(__('catalog.listing_type'))
                ->options([
                    'ready' => __('catalog.ready'),
                    'commission' => __('catalog.commission'),
                ])
                ->default('ready')
                ->live()
                ->columnSpanFull(),
            TextInput::make('price')
                ->label(__('catalog.price', ['currency' => is_string(config('commerce.currency')) ? (string) config('commerce.currency') : 'SAR']))
                ->numeric()
                ->minValue(0)
                ->columnSpanFull(),
            FileUpload::make('photos')
                ->label(__('catalog.photos'))
                ->image()
                ->multiple()
                ->maxFiles(8)
                ->maxSize(10240)
                ->storeFiles(false)
                ->columnSpanFull(),
            Textarea::make('description')
                ->label(__('catalog.description'))
                ->maxLength(5000)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('text')
                    ->label(__('catalog.text'))
                    ->limit(30)
                    ->weight(FontWeight::Bold),
                TextColumn::make('script.name_'.app()->getLocale()),
                TextColumn::make('price_minor')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? 'â€”' : Money::format($state).' SAR'),
                TextColumn::make('sold_at')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state !== null ? __('commerce.sold') : __('catalog.state_approved'))
                    ->color(fn ($state) => $state !== null ? UiColor::Gray : UiColor::Emerald),
                TextColumn::make('state')
                    ->badge()
                    ->color(fn (string $state): array => match ($state) {
                        'approved' => UiColor::Emerald,
                        'rejected' => UiColor::Red,
                        'pending_review' => UiColor::Amber,
                        default => UiColor::Gray,
                    })
                    ->formatStateUsing(fn (string $state): string => __("catalog.state_$state")),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label(__('studio.publish'))
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Piece $record): bool => in_array($record->state::getMorphClass(), ['draft', 'rejected'], true))
                    ->action(function (LivewireComponent $livewire, Piece $record): void {
                        /** @var User|null $seller */
                        $seller = Auth::user();

                        if ($seller instanceof User) {
                            app(PublishPiece::class)->handle($seller, $record);
                            $livewire->dispatch('refresh');
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ShopPiecesPages\ListShopPieces::route('/'),
            'create' => ShopPiecesPages\CreateShopPiece::route('/create'),
            'edit' => ShopPiecesPages\EditShopPiece::route('/{record}/edit'),
        ];
    }
}
