<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Moderation\Actions\ResolveReport;
use App\Domain\Moderation\Models\Report;
use App\Filament\Admin\Resources\ReportsResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color as UiColor;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;

final class ReportsResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = Report::query()->where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['reporter:id,name', 'reportable']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reporter.name')->label(__('reports.reporter')),
                TextColumn::make('reason')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("reports.reason_$state")),
                TextColumn::make('note')->limit(50)->placeholder('—'),
                TextColumn::make('subject')
                    ->state(function (Report $record): string {
                        $subject = $record->reportable;

                        if ($subject instanceof Piece) {
                            return __('reports.subject_piece').': '.Str::limit($subject->text, 24);
                        }

                        if ($subject instanceof User) {
                            return __('reports.subject_user').': '.$subject->name;
                        }

                        return $subject === null ? '—' : class_basename($subject);
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): array => match ($state) {
                        'pending' => UiColor::Amber,
                        'resolved' => UiColor::Emerald,
                        default => UiColor::Gray,
                    })
                    ->formatStateUsing(fn (string $state): string => __("reports.status_$state")),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label(__('reports.resolve'))
                    ->color('success')
                    ->visible(fn (Report $record): bool => $record->status === 'pending')
                    ->schema([
                        Textarea::make('note')->maxLength(1000),
                    ])
                    ->action(function (LivewireComponent $livewire, Report $record, array $data): void {
                        app(ResolveReport::class)->handle(
                            self::admin(),
                            $record,
                            'resolved',
                            isset($data['note']) && is_string($data['note']) ? $data['note'] : null,
                        );
                        $livewire->dispatch('refresh');
                    }),
                Action::make('dismiss')
                    ->label(__('reports.dismiss'))
                    ->color('gray')
                    ->visible(fn (Report $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (LivewireComponent $livewire, Report $record): void {
                        app(ResolveReport::class)->handle(self::admin(), $record, 'dismissed');
                        $livewire->dispatch('refresh');
                    }),
            ]);
    }

    private static function admin(): User
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_if($user === null || ! $user->hasRole('admin'), 403);

        return $user;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),        ];
    }
}
