<?php

namespace App\Http\Controllers\Catalog;

use App\Domain\Catalog\Actions\CreatePiece;
use App\Domain\Catalog\Actions\PublishPiece;
use App\Domain\Catalog\Models\Color;
use App\Domain\Catalog\Models\Medium;
use App\Domain\Catalog\Models\Script;
use App\Domain\Catalog\Models\Theme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CreatePieceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PieceController extends Controller
{
    public function create(): View
    {
        return view('pages.create-piece', [
            'scripts' => Script::query()->orderBy('sort')->get(),
            'mediums' => Medium::query()->orderBy('sort')->get(),
            'themes' => Theme::query()->orderBy('sort')->get(),
            'colors' => Color::query()->orderBy('sort')->get(),
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
