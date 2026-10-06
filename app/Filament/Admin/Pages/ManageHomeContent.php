<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Content\Support\HomeContent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

final class ManageHomeContent extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'home-content';

    protected string $view = 'filament.admin.pages.manage-home-content';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('content.nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('content.title');
    }

    public function mount(): void
    {
        $this->form->fill(app(HomeContent::class)->editable());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('sections')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make(__('content.hero'))
                            ->schema([
                                self::pair('hero.title', 'heading'),
                                self::pair('hero.title_accent', 'accent', help: __('content.accent_help')),
                                self::pair('hero.subtitle', 'subtitle', long: true),
                                self::pair('hero.cta_browse', 'cta_browse', help: __('content.buttons_help')),
                                self::pair('hero.cta_commission', 'cta_commission'),
                            ]),
                        Tab::make(__('content.marquee'))
                            ->schema([self::visible('marquee')]),
                        Tab::make(__('content.feed'))
                            ->schema([self::visible('feed')]),
                        Tab::make(__('content.how'))
                            ->schema([
                                self::visible('how'),
                                self::pair('how.title', 'heading'),
                                self::pair('how.title_accent', 'accent', help: __('content.accent_help')),
                                self::pair('how.subtitle', 'subtitle', long: true),
                                Repeater::make('how.steps')
                                    ->label(__('content.steps'))
                                    ->schema([
                                        self::pair('title', 'heading'),
                                        self::pair('body', 'body', long: true),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => $state['title_ar'] ?? null)
                                    ->addActionLabel(__('content.add_step'))
                                    ->reorderable()
                                    ->collapsible()
                                    ->defaultItems(0),
                            ]),
                        Tab::make(__('content.faq'))
                            ->schema([
                                self::visible('faq'),
                                self::pair('faq.title', 'heading'),
                                Repeater::make('faq.items')
                                    ->label(__('content.questions'))
                                    ->schema([
                                        self::pair('question', 'question'),
                                        self::pair('answer', 'answer', long: true),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => $state['question_ar'] ?? null)
                                    ->addActionLabel(__('content.add_question'))
                                    ->reorderable()
                                    ->collapsible()
                                    ->defaultItems(0),
                            ]),
                        Tab::make(__('content.cta'))
                            ->schema([
                                self::visible('cta'),
                                self::pair('cta.title', 'heading'),
                                self::pair('cta.subtitle', 'subtitle', long: true),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        app(HomeContent::class)->save($this->form->getState());

        Notification::make()
            ->success()
            ->title(__('content.saved'))
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(__('content.view_home'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(route('home'))
                ->openUrlInNewTab(),
        ];
    }

    private static function visible(string $section): Toggle
    {
        return Toggle::make("{$section}.is_visible")->label(__('content.visible'));
    }

    /** The same field side by side in Arabic and English. */
    private static function pair(string $field, string $label, bool $long = false, ?string $help = null): Grid
    {
        $inputs = array_map(function (string $locale) use ($field, $label, $long, $help) {
            $input = $long
                ? Textarea::make("{$field}_{$locale}")->rows(3)->maxLength(1000)
                : TextInput::make("{$field}_{$locale}")->maxLength(255);

            return $input
                ->label(__("content.{$label}").' ('.__("content.locale_{$locale}").')')
                ->helperText($locale === 'ar' ? $help : null)
                ->extraInputAttributes(['dir' => $locale === 'ar' ? 'rtl' : 'ltr']);
        }, HomeContent::LOCALES);

        return Grid::make(2)->schema($inputs);
    }
}
