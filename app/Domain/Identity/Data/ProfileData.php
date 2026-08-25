<?php

namespace App\Domain\Identity\Data;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

final class ProfileData extends Data
{
    public function __construct(
        public string $name,
        public string $username,
        public ?string $bio,
        public ?string $locale,
        public ?UploadedFile $avatar,
        public ?UploadedFile $cover,
    ) {}
}
