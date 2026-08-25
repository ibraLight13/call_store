<?php

namespace App\Http\Requests\Settings;

use App\Domain\Identity\Data\ProfileData;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[\pL\pN_.-]+$/u', Rule::unique('users', 'username')->ignore($this->actor()->id)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'locale' => ['nullable', 'in:ar,en'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'cover' => ['nullable', 'image', 'max:8192'],
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

    public function profileData(): ProfileData
    {
        $requestedLocale = $this->str('locale')->toString();
        $locale = in_array($requestedLocale, ['ar', 'en'], true) ? $requestedLocale : null;

        return new ProfileData(
            name: $this->str('name')->toString(),
            username: $this->str('username')->toString(),
            bio: $this->filled('bio') ? $this->str('bio')->toString() : null,
            locale: $locale,
            avatar: $this->imageFile('avatar'),
            cover: $this->imageFile('cover'),
        );
    }

    private function imageFile(string $key): ?UploadedFile
    {
        $file = $this->file($key);

        return $file instanceof UploadedFile && $file->isValid() ? $file : null;
    }
}
