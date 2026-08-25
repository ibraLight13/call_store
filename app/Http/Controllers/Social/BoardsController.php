<?php

namespace App\Http\Controllers\Social;

use App\Domain\Social\Models\Board;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class BoardsController extends Controller
{
    use ResolvesActor;

    public function index(Request $request): View
    {
        $boards = Board::query()
            ->where('user_id', $this->actor($request)->id)
            ->withCount('pieces')
            ->latest()
            ->get();

        return view('pages.boards', ['boards' => $boards]);
    }

    public function show(Request $request, Board $board): View
    {
        Gate::authorize('view', $board);

        $pieces = $board->pieces()
            ->approved()
            ->with(['seller:id,name,username', 'script', 'color'])
            ->get();

        return view('pages.board', [
            'board' => $board->loadCount('pieces'),
            'pieces' => $pieces,
        ]);
    }
}
