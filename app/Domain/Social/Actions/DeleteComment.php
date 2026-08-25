<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class DeleteComment
{
    public function handle(User $actor, Comment $comment): void
    {
        Gate::forUser($actor)->authorize('delete', $comment);

        // One level of replies: removing a top-level comment removes its replies.
        Comment::query()->where('parent_id', $comment->id)->delete();
        $comment->delete();
    }
}
