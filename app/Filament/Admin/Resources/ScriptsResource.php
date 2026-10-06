<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Catalog\Models\Script;
use App\Filament\Admin\Resources\ScriptsResource\Pages;
use App\Support\Palette;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/** Calligraphy script styles: the home page strip, explore filters and the font each piece is drawn in. */
final class ScriptsResource extends Resource
{
    protected static ?string $model = Script::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'scripts';

    public static function getNavigationLabel(): string
    {
        return __('content.scripts_nav');
    }

    public static function getModelLabel(): string
    {
        return __('content.script');
    }

    public static function getPluralModelLabel(): string
    {
        return __('content.scripts_nav');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_ar')
                ->label(__('content.name_ar'))
                ->required()
                ->maxLength(255)
                ->extraInputAttributes(['dir' => 'rtl']),
            TextInput::make('name_en')
                ->label(__('content.name_en'))
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                    if ($operation === 'create') {
                        $set('slug', Str::slug((string) $state));
                    }
                })
                ->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('slug')
                ->label(__('content.slug'))
                ->helperText(__('content.slug_help'))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->regex('/^[a-z0-9-]+$/'),
            Select::make('font')
                ->label(__('content.font'))
                ->options(self::fontOptions())
                ->placeholder(__('content.font_default'))
                ->native(false),
            Select::make('weight')
                ->label(__('content.weight'))
                ->options([400 => '400', 500 => '500', 600 => '600', 700 => '700'])
                ->placeholder(__('content.font_default'))
                ->native(false),
            TextInput::make('sort')
                ->label(__('content.sort'))
                ->numeric()
                ->minValue(0)
                ->maxValue(255)
                ->default(fn (): int => min(255, (int) Script::query()->max('sort') + 1)),
            Toggle::make('show_on_home')
                ->label(__('content.show_on_home'))
                ->default(true)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name_ar')
                    ->label(__('content.name_ar'))
                    ->extraAttributes(fn (Script $record): array => [
                        'style' => 'font-family:'.Palette::font($record->slug, $record->font).';font-weight:'.Palette::weight($record->slug, $record->weight),
                    ])
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label(__('content.name_en'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label(__('content.slug'))
                    ->color('gray'),
                ToggleColumn::make('show_on_home')
                    ->label(__('content.show_on_home')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(__('content.script_delete_warning')),
            ]);
    }

    /** @return array<string, string> font key => readable family name */
    public static function fontOptions(): array
    {
        $fonts = config('catalog.fonts');

        if (! is_array($fonts)) {
            return [];
        }

        return collect($fonts)
            ->filter(fn (mixed $stack): bool => is_string($stack))
            ->map(fn (string $stack): string => trim(Str::before($stack, ','), " '\""))
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageScripts::route('/'),
        ];
    }
}
