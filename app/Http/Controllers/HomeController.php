<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\Models\Piece;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function index(): View
    {
        $pieces = Piece::query()
            ->approved()
            ->with(['seller:id,name,username', 'script', 'color'])
            ->latest('published_at')
            ->limit(48)
            ->get();

        return view('pages.home', ['pieces' => $pieces]);
    }
}
