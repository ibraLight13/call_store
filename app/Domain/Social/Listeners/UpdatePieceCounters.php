<?php

namespace App\Domain\Social\Listeners;

use App\Domain\Social\Events\PieceLiked;
use App\Domain\Social\Events\PieceSaved;
use App\Domain\Social\Events\PieceUnliked;
use App\Domain\Social\Events\PieceUnsaved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

/**
 * Denormalized counters are maintained with single atomic UPDATE statements,
 * so concurrent likes/saves stay correct. Swap to a queued listener (add
 * ShouldQueue) once Redis-backed queues land; the event contract is stable.
 */
final class UpdatePieceCounters
{
    private const DELTAS = [
        PieceLiked::class => ['likes_count', +1],
        PieceUnliked::class => ['likes_count', -1],
        PieceSaved::class => ['saves_count', +1],
        PieceUnsaved::class => ['saves_count', -1],
    ];

    public function handle(PieceLiked|PieceUnliked|PieceSaved|PieceUnsaved $event): void
    {
        [$column, $delta] = self::DELTAS[$event::class];

        DB::table('pieces')
            ->where('id', $event->piece->id)
            ->increment($column, $delta);
    }
}
