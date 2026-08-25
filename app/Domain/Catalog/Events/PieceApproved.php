<?php

namespace App\Domain\Catalog\Events;

use App\Domain\Catalog\Models\Piece;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PieceApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Piece $piece) {}
}
