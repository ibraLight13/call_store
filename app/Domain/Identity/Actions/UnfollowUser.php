<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Follow;
use App\Models\User;

final class UnfollowUser
{
    public function handle(User $follower, User $target): void
    {
        Follow::query()
            ->where('follower_id', $follower->id)
            ->where('following_id', $target->id)
            ->delete();
    }
}
