<?php

namespace App\Domain\Social\Actions;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Social\Models\Comment;
use App\Domain\Social\Notifications\CommentAdded;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

final class AddComment
{
    public function handle(User $actor, Piece $piece, string $body, ?int $parentId = null): Comment
    {
        $body = trim($body);

        if ($body === '' || mb_strlen($body) > 2000) {
            throw new InvalidArgumentException(__('social.comment_invalid'));
        }

        $parent = null;

        if ($parentId !== null) {
            $parent = Comment::query()->find($parentId);

            // One level of replies only: the target must be a top-level comment
            // on this same piece.
            if ($parent === null
                || (int) $parent->piece_id !== (int) $piece->id
                || $parent->parent_id !== null) {
                throw new InvalidArgumentException(__('social.reply_target_invalid'));
            }
        }

        $comment = Comment::query()->create([
            'user_id' => $actor->id,
            'piece_id' => $piece->id,
            'parent_id' => $parent?->id,
            'body' => $body,
        ]);

        $comment->setRelation('user', $actor);
        $comment->setRelation('piece', $piece);
        if ($parent !== null) {
            $parent->setRelation('user', User::query()->find($parent->user_id));
        }

        // Notify the artist about comments on their piece…
        if ((int) $piece->seller_id !== (int) $actor->id) {
            Notification::send(
                User::query()->whereKey($piece->seller_id)->get(),
                new CommentAdded($comment, 'piece'),
            );
        }

        // …and the parent author about replies (unless already notified above).
        if ($parent !== null
            && (int) $parent->user_id !== (int) $actor->id
            && (int) $parent->user_id !== (int) $piece->seller_id) {
            Notification::send(
                User::query()->whereKey($parent->user_id)->get(),
                new CommentAdded($comment, 'reply'),
            );
        }

        return $comment;
    }
}
