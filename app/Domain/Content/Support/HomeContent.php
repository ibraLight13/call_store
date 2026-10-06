<?php

namespace App\Domain\Content\Support;

use App\Domain\Content\Models\HomeSection;
use Illuminate\Support\Facades\Cache;

/**
 * Editable home page copy. Each section stores only what the admin changed;
 * anything left empty (or equal to the default) falls back to lang/{ar,en}/shell.php.
 */
final class HomeContent
{
    public const CACHE_KEY = 'home:sections';

    public const LOCALES = ['ar', 'en'];

    /** Sections in page order. The hero is always shown. */
    public const SECTIONS = ['hero', 'marquee', 'feed', 'how', 'faq', 'cta'];

    /** Single-text fields per section => default translation key. */
    private const FIELDS = [
        'hero' => [
            'title' => 'shell.home_title',
            'title_accent' => 'shell.home_title_accent',
            'subtitle' => 'shell.home_sub',
            'cta_browse' => 'shell.home_cta_browse',
            'cta_commission' => 'shell.home_cta_commission',
        ],
        'marquee' => [],
        'feed' => [],
        'how' => [
            'title' => 'shell.how_title',
            'title_accent' => 'shell.how_title_accent',
            'subtitle' => 'shell.how_sub',
        ],
        'faq' => [
            'title' => 'shell.faq_title',
        ],
        'cta' => [
            'title' => 'shell.cta_title',
            'subtitle' => 'shell.cta_sub',
        ],
    ];

    /** Repeatable lists per section: [state key, item fields => default key pattern, default count]. */
    private const LISTS = [
        'how' => ['steps', ['title' => 'shell.how_%d_t', 'body' => 'shell.how_%d_b'], 3],
        'faq' => ['items', ['question' => 'shell.faq_%d_q', 'answer' => 'shell.faq_%d_a'], 4],
    ];

    /**
     * Resolved copy for rendering the home page in one locale.
     *
     * @return array<string, array<string, mixed>>
     */
    public function forLocale(string $locale): array
    {
        $stored = $this->stored();
        $resolved = [];

        foreach (self::SECTIONS as $key) {
            $content = $stored[$key]['content'] ?? [];
            $section = ['visible' => $key === 'hero' || ($stored[$key]['is_visible'] ?? true)];

            foreach (self::FIELDS[$key] as $field => $default) {
                $value = trim((string) ($content["{$field}_{$locale}"] ?? ''));
                $section[$field] = $value !== '' ? $value : __($default, [], $locale);
            }

            if (isset(self::LISTS[$key])) {
                [$name, $fields] = self::LISTS[$key];
                $section[$name] = $this->resolveList($key, $content[$name] ?? null, $fields, $locale);
            }

            $resolved[$key] = $section;
        }

        return $resolved;
    }

    /**
     * Form state for the admin page: current values, pre-filled with the defaults.
     *
     * @return array<string, array<string, mixed>>
     */
    public function editable(): array
    {
        $stored = $this->stored();
        $state = [];

        foreach (self::SECTIONS as $key) {
            $content = $stored[$key]['content'] ?? [];
            $section = ['is_visible' => $stored[$key]['is_visible'] ?? true];

            foreach (self::FIELDS[$key] as $field => $default) {
                foreach (self::LOCALES as $locale) {
                    $value = trim((string) ($content["{$field}_{$locale}"] ?? ''));
                    $section["{$field}_{$locale}"] = $value !== '' ? $value : __($default, [], $locale);
                }
            }

            if (isset(self::LISTS[$key])) {
                $name = self::LISTS[$key][0];
                $section[$name] = is_array($content[$name] ?? null) ? $content[$name] : $this->defaultList($key);
            }

            $state[$key] = $section;
        }

        return $state;
    }

    /**
     * Persist admin form state, keeping only values that differ from the defaults.
     *
     * @param  array<string, mixed>  $state
     */
    public function save(array $state): void
    {
        foreach (self::SECTIONS as $key) {
            $input = is_array($state[$key] ?? null) ? $state[$key] : [];
            $content = [];

            foreach (self::FIELDS[$key] as $field => $default) {
                foreach (self::LOCALES as $locale) {
                    $value = trim((string) ($input["{$field}_{$locale}"] ?? ''));

                    if ($value !== '' && $value !== __($default, [], $locale)) {
                        $content["{$field}_{$locale}"] = $value;
                    }
                }
            }

            if (isset(self::LISTS[$key])) {
                [$name, $fields] = self::LISTS[$key];
                $list = $this->cleanList(is_array($input[$name] ?? null) ? $input[$name] : [], $fields);

                if ($list !== $this->defaultList($key)) {
                    // An emptied list is kept as [] so the section renders with no items.
                    $content[$name] = $list;
                }
            }

            HomeSection::query()->updateOrCreate(['key' => $key], [
                'is_visible' => $key === 'hero' || (bool) ($input['is_visible'] ?? true),
                'content' => $content === [] ? null : $content,
            ]);
        }
    }

    /**
     * @return array<string, array{is_visible: bool, content: array<string, mixed>}>
     */
    private function stored(): array
    {
        /** @var array<string, array{is_visible: bool, content: array<string, mixed>}> $rows */
        $rows = Cache::rememberForever(self::CACHE_KEY, fn (): array => HomeSection::query()
            ->get()
            ->mapWithKeys(fn (HomeSection $row): array => [
                $row->key => ['is_visible' => $row->is_visible, 'content' => $row->content ?? []],
            ])
            ->all());

        return $rows;
    }

    /**
     * @param  array<string, string>  $fields
     * @return list<array<string, string>>
     */
    private function resolveList(string $key, mixed $items, array $fields, string $locale): array
    {
        $items = is_array($items) ? $items : $this->defaultList($key);
        $other = $locale === 'ar' ? 'en' : 'ar';
        $resolved = [];

        foreach ($items as $item) {
            $row = [];

            foreach (array_keys($fields) as $field) {
                // An item missing this locale shows the other language rather than a blank.
                $value = trim((string) ($item["{$field}_{$locale}"] ?? ''));
                $row[$field] = $value !== '' ? $value : trim((string) ($item["{$field}_{$other}"] ?? ''));
            }

            if (implode('', $row) !== '') {
                $resolved[] = $row;
            }
        }

        return $resolved;
    }

    /**
     * @return list<array<string, string>>
     */
    private function defaultList(string $key): array
    {
        [, $fields, $count] = self::LISTS[$key];
        $list = [];

        for ($n = 1; $n <= $count; $n++) {
            $item = [];

            foreach ($fields as $field => $pattern) {
                foreach (self::LOCALES as $locale) {
                    $item["{$field}_{$locale}"] = __(sprintf($pattern, $n), [], $locale);
                }
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * @param  array<mixed>  $items
     * @param  array<string, string>  $fields
     * @return list<array<string, string>>
     */
    private function cleanList(array $items, array $fields): array
    {
        $list = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = [];

            foreach (array_keys($fields) as $field) {
                foreach (self::LOCALES as $locale) {
                    $row["{$field}_{$locale}"] = trim((string) ($item["{$field}_{$locale}"] ?? ''));
                }
            }

            if (implode('', $row) !== '') {
                $list[] = $row;
            }
        }

        return $list;
    }
}
