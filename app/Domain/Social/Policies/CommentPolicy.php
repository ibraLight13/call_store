<?php

namespace App\Domain\Social\Policies;

use App\Domain\Social\Models\Comment;
use App\Models\User;

final class CommentPolicy
{
    public function delete(User $user, Comment $comment): bool
    {
        if ((int) $comment->user_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        $piece = $comment->piece;

        return $piece !== null && (int) $piece->seller_id === (int) $user->id;
    }
}
