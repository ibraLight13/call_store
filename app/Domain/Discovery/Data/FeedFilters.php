<?php

namespace App\Domain\Discovery\Data;

use Spatie\LaravelData\Data;

final class FeedFilters extends Data
{
    public const PRICE_BANDS = [
        'lt500' => [0, 50_000],
        '500to1500' => [50_000, 150_000],
        'gt1500' => [150_000, null],
    ];

    public function __construct(
        public ?string $q,
        public ?int $scriptId,
        public ?int $themeId,
        public ?int $mediumId,
        public ?int $colorId,
        public ?string $type,
        public ?string $priceBand,
    ) {
        if ($this->type !== null && ! in_array($this->type, ['ready', 'commission'], true)) {
            $this->type = null;
        }

        if ($this->priceBand !== null && ! array_key_exists($this->priceBand, self::PRICE_BANDS)) {
            $this->priceBand = null;
        }
    }

    /** @return array{0: int, 1: int|null}|null */
    public function priceRange(): ?array
    {
        return $this->priceBand === null ? null : self::PRICE_BANDS[$this->priceBand];
    }

    public function hasActiveFilters(): bool
    {
        return $this->scriptId !== null
            || $this->themeId !== null
            || $this->mediumId !== null
            || $this->colorId !== null
            || $this->type !== null
            || $this->priceBand !== null;
    }

    /**
     * @param  array<array-key, mixed>  $params
     */
    public static function fromRequestParams(array $params): self
    {
        $int = fn (mixed $value): ?int => is_int($value) && $value > 0
            ? $value
            : (is_string($value) && ctype_digit($value) && (int) $value > 0 ? (int) $value : null);
        $str = fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null;

        return new self(
            q: isset($params['q']) ? $str($params['q']) : null,
            scriptId: $int($params['script'] ?? null),
            themeId: $int($params['theme'] ?? null),
            mediumId: $int($params['medium'] ?? null),
            colorId: $int($params['color'] ?? null),
            type: isset($params['type']) ? $str($params['type']) : null,
            priceBand: isset($params['price']) ? $str($params['price']) : null,
        );
    }

    /** @return array<string, string|int> */
    public function toQueryParams(): array
    {
        return array_filter([
            'q' => $this->q,
            'script' => $this->scriptId,
            'theme' => $this->themeId,
            'medium' => $this->mediumId,
            'color' => $this->colorId,
            'type' => $this->type,
            'price' => $this->priceBand,
        ], fn ($value): bool => $value !== null && $value !== '');
    }
}
