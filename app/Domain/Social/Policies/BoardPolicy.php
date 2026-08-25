<?php

namespace App\Domain\Social\Policies;

use App\Domain\Social\Models\Board;
use App\Models\User;

final class BoardPolicy
{
    public function manage(User $user, Board $board): bool
    {
        return (int) $board->user_id === (int) $user->id;
    }

    public function view(User $user, Board $board): bool
    {
        return ! $board->is_private || (int) $board->user_id === (int) $user->id;
    }
}
