<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Follow;
use App\Models\User;
use DomainException;

final class FollowUser
{
    public function handle(User $follower, User $target): void
    {
        if ($follower->id === $target->id) {
            throw new DomainException(__('shell.cannot_follow_self'));
        }

        Follow::query()->firstOrCreate([
            'follower_id' => $follower->id,
            'following_id' => $target->id,
        ]);
    }
}
