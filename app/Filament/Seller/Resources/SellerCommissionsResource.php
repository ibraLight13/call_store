<?php

namespace App\Filament\Seller\Resources;

use App\Domain\Commissions\Actions\DeclineCommission;
use App\Domain\Commissions\Actions\QuoteCommission;
use App\Domain\Commissions\Actions\ShipCommission;
use App\Domain\Commissions\Actions\StartCommissionWork;
use App\Domain\Commissions\Actions\SubmitCommissionProof;
use App\Domain\Commissions\Models\Commission;
use App\Filament\Seller\Resources\SellerCommissionsResource\Pages as CommissionPages;
use App\Models\User;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color as UiColor;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Component as LivewireComponent;

final class SellerCommissionsResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('studio.commissions_nav');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('seller_id', Auth::id())->with('buyer');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->weight(FontWeight::Bold)->sortable(),
                TextColumn::make('text')->limit(30),
                TextColumn::make('buyer.name')->label(__('studio.client')),
                TextColumn::make('quote_minor')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : Money::format($state).' SAR'),
                TextColumn::make('revision_count')->label(__('studio.revisions'))->alignCenter(),
                TextColumn::make('state')
                    ->badge()
                    ->color(fn (string $state): array => match ($state) {
                        'approved', 'completed' => UiColor::Emerald,
                        'declined', 'cancelled' => UiColor::Red,
                        'balance_paid', 'shipped' => UiColor::Sky,
                        default => UiColor::Amber,
                    })
                    ->formatStateUsing(fn (string $state): string => __("commission_state_$state")),
            ])
            ->recordActions(self::lifecycleActions());
    }

    /**
     * Every lifecycle action delegates to its Phase 6 Action — the panel is a
     * thin skin over the same domain API the consumer app uses.
     *
     * @return list<Action>
     */
    private static function lifecycleActions(): array
    {
        $seller = static fn (): ?User => Auth::user() instanceof User ? Auth::user() : null;
        $isState = fn (array $states) => fn (Commission $record): bool => in_array($record->state::getMorphClass(), $states, true);

        return [
            Action::make('quote')
                ->label(__('commissions.send_quote'))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->visible(fn (Commission $record): bool => $isState(['requested'])($record))
                ->schema([
                    TextInput::make('quote')->numeric()->minValue(50)->required(),
                    Textarea::make('note')->maxLength(2000),
                ])
                ->action(function (Commission $record, array $data) use ($seller): void {
                    $user = $seller();

                    if ($user !== null && isset($data['quote']) && is_numeric($data['quote'])) {
                        app(QuoteCommission::class)->handle(
                            $user, $record, (int) round(((float) $data['quote']) * 100),
                            isset($data['note']) && is_string($data['note']) ? $data['note'] : null,
                        );
                    }
                }),
            Action::make('decline')
                ->label(__('commissions.decline_short'))
                ->color('danger')
                ->visible(fn (Commission $record): bool => $isState(['requested', 'quoted'])($record))
                ->requiresConfirmation()
                ->action(function (LivewireComponent $livewire, Commission $record) use ($seller): void {
                    $user = $seller();

                    if ($user !== null) {
                        app(DeclineCommission::class)->handle(
                            $user, $record, __('studio.decline_default'),
                        );
                        $livewire->dispatch('refresh');
                    }
                }),
            Action::make('start')
                ->label(__('commissions.start_work'))
                ->color('warning')
                ->visible(fn (Commission $record): bool => $isState(['deposit_paid'])($record))
                ->requiresConfirmation()
                ->action(function (LivewireComponent $livewire, Commission $record) use ($seller): void {
                    $user = $seller();

                    if ($user !== null) {
                        app(StartCommissionWork::class)->handle($user, $record);
                        $livewire->dispatch('refresh');
                    }
                }),
            Action::make('proof')
                ->label(__('commissions.submit_proof_btn'))
                ->icon(Heroicon::OutlinedPhoto)
                ->color('info')
                ->visible(fn (Commission $record): bool => $isState(['in_progress', 'revision_requested'])($record))
                ->schema([
                    FileUpload::make('proof_image')->image()->required()->maxSize(10240)->storeFiles(false),
                    Textarea::make('seller_note')->maxLength(2000),
                ])
                ->action(function (Commission $record, array $data) use ($seller): void {
                    $user = $seller();
                    $image = $data['proof_image'] ?? null;

                    if ($user !== null && $image instanceof UploadedFile) {
                        app(SubmitCommissionProof::class)->handle(
                            $user, $record, $image,
                            isset($data['seller_note']) && is_string($data['seller_note']) ? $data['seller_note'] : null,
                        );
                    }
                }),
            Action::make('ship')
                ->label(__('commerce.mark_shipped'))
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary')
                ->visible(fn (Commission $record): bool => $isState(['balance_paid'])($record))
                ->schema([
                    TextInput::make('tracking_number')->maxLength(80),
                ])
                ->action(function (LivewireComponent $livewire, Commission $record, array $data) use ($seller): void {
                    $user = $seller();

                    if ($user !== null) {
                        app(ShipCommission::class)->handle(
                            $user, $record,
                            isset($data['tracking_number']) && is_string($data['tracking_number']) ? $data['tracking_number'] : null,
                        );
                        $livewire->dispatch('refresh');
                    }
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => CommissionPages\ListSellerCommissions::route('/'),
        ];
    }
}
