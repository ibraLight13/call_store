<?php

namespace App\Http\Controllers\Catalog;

use App\Domain\Catalog\Models\Piece;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PiecesController extends Controller
{
    public function show(Request $request, Piece $piece): View
    {
        $viewer = $request->user();

        $published = $piece->state::getMorphClass() === 'approved';
        $ownerOrAdmin = $viewer !== null && ((int) $piece->seller_id === (int) $viewer->id || $viewer->hasRole('admin'));

        if (! $published && ! $ownerOrAdmin) {
            abort(404);
        }

        $related = Piece::query()
            ->approved()
            ->with(['seller:id,name,username', 'script', 'color'])
            ->whereKeyNot($piece->getKey())
            ->when(
                $piece->theme_id !== null,
                fn (Builder $q) => $q->where('theme_id', $piece->theme_id),
                fn (Builder $q) => $q->where('script_id', $piece->script_id),
            )
            ->latest('published_at')
            ->limit(10)
            ->get();

        return view('pages.piece', [
            'piece' => $piece->load(['script', 'medium', 'theme', 'color', 'seller']),
            'related' => $related,
        ]);
    }
}
