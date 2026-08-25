<?php

namespace App\Domain\Identity\Actions;

use App\Models\User;

final class UpdateLocale
{
    public function handle(User $user, string $locale): void
    {
        if (! in_array($locale, ['ar', 'en'], true)) {
            return;
        }

        if ($user->locale === $locale) {
            return;
        }

        $user->forceFill(['locale' => $locale])->save();
    }
}
