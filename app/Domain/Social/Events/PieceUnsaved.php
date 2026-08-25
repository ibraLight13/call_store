<?php

namespace App\Domain\Social\Events;

use App\Domain\Catalog\Models\Piece;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PieceUnsaved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Piece $piece) {}
}
