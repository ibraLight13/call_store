<?php

namespace App\Domain\Catalog\Policies;

use App\Domain\Catalog\Models\Piece;
use App\Models\User;

final class PiecePolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['seller', 'admin']);
    }

    public function update(User $user, Piece $piece): bool
    {
        return $piece->seller_id === $user->id;
    }

    public function publish(User $user, Piece $piece): bool
    {
        return $piece->seller_id === $user->id;
    }

    public function moderate(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
