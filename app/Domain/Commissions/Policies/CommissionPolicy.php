<?php

namespace App\Domain\Commissions\Policies;

use App\Domain\Commissions\Models\Commission;
use App\Models\User;

final class CommissionPolicy
{
    public function manageAsSeller(User $user, Commission $commission): bool
    {
        return (int) $commission->seller_id === (int) $user->id || $user->hasRole('admin');
    }

    public function manageAsBuyer(User $user, Commission $commission): bool
    {
        return (int) $commission->buyer_id === (int) $user->id;
    }

    public function view(User $user, Commission $commission): bool
    {
        return (int) $commission->buyer_id === (int) $user->id
            || (int) $commission->seller_id === (int) $user->id
            || $user->hasRole('admin');
    }
}
