<?php

namespace App\Http\Controllers\Social;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Social\Actions\AddComment;
use App\Domain\Social\Actions\DeleteComment;
use App\Domain\Social\Models\Comment;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CommentController extends Controller
{
    use ResolvesActor;

    public function store(Request $request, Piece $piece): RedirectResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parentId = $request->filled('parent_id') && $request->integer('parent_id') > 0
            ? (int) $request->integer('parent_id')
            : null;

        app(AddComment::class)->handle(
            actor: $this->actor($request),
            piece: $piece,
            body: $request->str('body')->toString(),
            parentId: $parentId,
        );

        return back()->with('status', __('social.comment_added'));
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        app(DeleteComment::class)->handle($this->actor($request), $comment);

        return back()->with('status', __('social.comment_deleted'));
    }
}
