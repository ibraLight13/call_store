<?php

namespace App\Http\Requests\Catalog;

use App\Domain\Catalog\Data\PieceData;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class CreatePieceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return non-empty-array<non-empty-string, non-empty-list<mixed>>
     */
    public function rules(): array
    {
        $taxonomy = fn (string $table): array => ['nullable', 'integer', "exists:$table,id"];

        return [
            'text' => ['required', 'string', 'min:2', 'max:500'],
            'transliteration' => ['nullable', 'string', 'max:255'],
            'text_language' => ['required', 'in:ar,en'],
            'script_id' => $taxonomy('scripts'),
            'medium_id' => $taxonomy('mediums'),
            'theme_id' => $taxonomy('themes'),
            'color_id' => $taxonomy('colors'),
            'width_cm' => ['nullable', 'integer', 'min:5', 'max:500'],
            'height_cm' => ['nullable', 'integer', 'min:5', 'max:500'],
            'listing_type' => ['required', 'in:ready,commission'],
            'price' => ['required_if:listing_type,ready', 'nullable', 'numeric', 'min:0', 'max:9999999'],
            'currency' => ['required', 'size:3'],
            'unique_stock' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'max:10240'],
        ];
    }

    public function actor(): User
    {
        $user = parent::user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }

    public function pieceData(): PieceData
    {
        $price = $this->input('price');

        $photos = array_values(array_filter(
            (array) $this->file('photos', []),
            fn ($photo): bool => $photo instanceof UploadedFile,
        ));

        return new PieceData(
            text: $this->str('text')->toString(),
            textLanguage: $this->str('text_language')->toString() === 'en' ? 'en' : 'ar',
            transliteration: $this->filled('transliteration') ? $this->str('transliteration')->toString() : null,
            scriptId: $this->filled('script_id') ? $this->integer('script_id') : null,
            mediumId: $this->filled('medium_id') ? $this->integer('medium_id') : null,
            themeId: $this->filled('theme_id') ? $this->integer('theme_id') : null,
            colorId: $this->filled('color_id') ? $this->integer('color_id') : null,
            widthCm: $this->filled('width_cm') ? $this->integer('width_cm') : null,
            heightCm: $this->filled('height_cm') ? $this->integer('height_cm') : null,
            listingType: $this->str('listing_type')->toString() === 'commission' ? 'commission' : 'ready',
            priceMinor: is_numeric($price) ? (int) round(((float) $price) * 100) : null,
            currency: $this->str('currency')->toString() ?: 'SAR',
            uniqueStock: $this->boolean('unique_stock'),
            description: $this->filled('description') ? $this->str('description')->toString() : null,
            photos: $photos,
        );
    }
}
