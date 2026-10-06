<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\Support\TaxonomyRepository;
use App\Domain\Content\Support\HomeContent;
use App\Domain\Discovery\Queries\FeedQuery;
use App\Support\PinPresenter;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function index(FeedQuery $feed, TaxonomyRepository $taxonomy, HomeContent $content): View
    {
        // Same cached first page the Livewire feed renders: no extra query on a warm cache.
        $newest = $feed->firstPage()->first();

        return view('pages.home', [
            'featured' => $newest !== null ? PinPresenter::present($newest, 0) : null,
            'scripts' => $taxonomy->all()['scripts'],
            'home' => $content->forLocale(app()->getLocale()),
        ]);
    }
}
