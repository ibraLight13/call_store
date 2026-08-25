<?php

namespace App\Livewire;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Social\Actions\LikePiece;
use App\Domain\Social\Actions\UnlikePiece;
use App\Domain\Social\Models\Like;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class LikeButton extends Component
{
    public Piece $piece;

    public bool $liked = false;

    public int $count = 0;

    public function mount(Piece $piece): void
    {
        $viewer = Auth::user();

        $this->liked = $viewer !== null
            && Like::query()
                ->where('user_id', $viewer->id)
                ->where('piece_id', $piece->id)
                ->exists();

        $this->count = (int) ($piece->likes_count ?? 0);
    }

    public function toggle(): void
    {
        $viewer = Auth::user();

        if (! $viewer instanceof User) {
            $this->redirectRoute('login');

            return;
        }

        if ($this->liked) {
            app(UnlikePiece::class)->handle($viewer, $piece = $this->piece);
            $this->liked = false;
            $this->count = max(0, $this->count - 1);

            return;
        }

        app(LikePiece::class)->handle($viewer, $this->piece);
        $this->liked = true;
        $this->count++;
    }
}
