<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\Models\Piece;
use App\Models\User;
use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [];

        $base = is_string(config('app.url')) ? rtrim((string) config('app.url'), '/') : url('/');

        $urls[] = ['loc' => $base.'/', 'priority' => '1.0', 'changefreq' => 'daily'];
        $urls[] = ['loc' => $base.'/explore', 'priority' => '0.9', 'changefreq' => 'daily'];

        foreach (User::query()->whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get(['id', 'username']) as $seller) {
            $urls[] = ['loc' => $base.'/@'.$seller->username, 'priority' => '0.7', 'changefreq' => 'weekly'];
        }

        Piece::query()
            ->approved()
            ->whereNull('sold_at')
            ->latest('published_at')
            ->limit(5000)
            ->get(['id', 'updated_at'])
            ->each(function (Piece $piece) use (&$urls, $base): void {
                $urls[] = [
                    'loc' => $base.'/pieces/'.$piece->id,
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                    'lastmod' => $piece->updated_at?->toAtomString(),
                ];
            });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
