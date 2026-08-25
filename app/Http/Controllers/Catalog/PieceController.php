<?php

namespace App\Http\Controllers\Catalog;

use App\Domain\Catalog\Actions\CreatePiece;
use App\Domain\Catalog\Actions\PublishPiece;
use App\Domain\Catalog\Support\TaxonomyRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CreatePieceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PieceController extends Controller
{
    public function create(): View
    {
        $taxonomy = app(TaxonomyRepository::class)->all();

        return view('pages.create-piece', [
            'scripts' => $taxonomy['scripts'],
            'mediums' => $taxonomy['mediums'],
            'themes' => $taxonomy['themes'],
            'colors' => $taxonomy['colors'],
        ]);
    }

    public function store(CreatePieceRequest $request): RedirectResponse
    {
        $piece = app(CreatePiece::class)->handle($request->actor(), $request->pieceData());

        if ($request->boolean('submit_for_review')) {
            app(PublishPiece::class)->handle($request->actor(), $piece);
        }

        return redirect()
            ->route('home')
            ->with('status', __('catalog.submitted'));
    }
}
