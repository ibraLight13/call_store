<?php

namespace App\Domain\Discovery\Listeners;

use App\Domain\Catalog\Events\PieceApproved;
use App\Domain\Catalog\Events\PieceRejected;
use App\Domain\Discovery\Cache\FeedCache;
use Illuminate\Support\Facades\Cache;

final class FlushDiscoveryCache
{
    public function handle(PieceApproved|PieceRejected $event): void
    {
        FeedCache::flush();
        Cache::forget(FeedCache::TAXONOMY_KEY);
    }
}
