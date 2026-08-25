<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\Notifications\CommissionUpdate;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies the other party of a commission about an event, keyed by context.
 * The actor never receives their own notification.
 */
final class NotifyCounterparty
{
    public function handle(Commission $commission, string $audience, string $context): void
    {
        $recipientId = match ($audience) {
            'buyer' => $commission->buyer_id,
            'seller' => $commission->seller_id,
            default => null,
        };

        if ($recipientId === null) {
            return;
        }

        Notification::send(
            User::query()->whereKey($recipientId)->get(),
            new CommissionUpdate($commission, $context),
        );
    }
}
