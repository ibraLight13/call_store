<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Catalog\Actions\ApprovePiece;
use App\Domain\Catalog\Actions\RejectPiece;
use App\Domain\Catalog\Models\Piece;
use App\Filament\Admin\Resources\PiecesResource\Pages;
use App\Models\User;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color as UiColor;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Component as LivewireComponent;

final class PiecesResource extends Resource
{
    protected static ?string $model = Piece::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('text')
                    ->searchable()
                    ->limit(40)
                    ->weight(FontWeight::Bold),
                TextColumn::make('script.name_'.app()->getLocale()),
                TextColumn::make('theme.name_'.app()->getLocale())
                    ->badge()
                    ->default('-'),
                TextColumn::make('seller.name')
                    ->label(__('catalog.seller'))
                    ->searchable(),
                TextColumn::make('price_minor')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : Money::format($state)),
                TextColumn::make('state')
                    ->badge()
                    ->color(fn (string $state): array => match ($state) {
                        'approved' => UiColor::Emerald,
                        'rejected' => UiColor::Red,
                        'pending_review' => UiColor::Amber,
                        default => UiColor::Gray,
                    })
                    ->formatStateUsing(fn (string $state): string => __("catalog.state_$state")),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->options([
                        'pending_review' => __('catalog.state_pending'),
                        'draft' => __('catalog.state_draft'),
                        'approved' => __('catalog.state_approved'),
                        'rejected' => __('catalog.state_rejected'),
                    ])
                    ->default('pending_review'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('catalog.approve'))
                    ->color('success')
                    ->icon(Heroicon::OutlinedCheck)
                    ->requiresConfirmation()
                    ->visible(fn (Piece $record): bool => $record->state::getMorphClass() === 'pending_review')
                    ->action(function (LivewireComponent $livewire, Piece $record): void {
                        app(ApprovePiece::class)->handle(self::moderator(), $record);
                        $livewire->dispatch('refresh');
                    }),
                Action::make('reject')
                    ->label(__('catalog.reject'))
                    ->color('danger')
                    ->icon(Heroicon::OutlinedXMark)
                    ->visible(fn (Piece $record): bool => $record->state::getMorphClass() === 'pending_review')
                    ->schema([
                        Textarea::make('reason')
                            ->label(__('catalog.reject_reason'))
                            ->placeholder(__('catalog.reject_reason_ph'))
                            ->required()
                            ->minLength(5)
                            ->maxLength(1000),
                    ])
                    ->modalWidth(Width::Medium)
                    ->action(function (LivewireComponent $livewire, Piece $record, array $data): void {
                        $reason = $data['reason'] ?? '';

                        app(RejectPiece::class)->handle(self::moderator(), $record, is_string($reason) ? $reason : '');
                        $livewire->dispatch('refresh');
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPieces::route('/'),
        ];
    }

    private static function moderator(): User
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_if($user === null, 403);

        return $user;
    }
}
