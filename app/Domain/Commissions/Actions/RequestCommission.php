<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\Notifications\CommissionUpdate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class RequestCommission
{
    /**
     * @param  array{ text: string, text_language?: string, script_id?: int|null, medium_id?: int|null,
     *                width_cm?: int|null, height_cm?: int|null, budget_minor?: int|null,
     *                deadline?: string|null, message?: string|null }  $data
     */
    public function handle(User $buyer, User $seller, array $data): Commission
    {
        if ((int) $buyer->id === (int) $seller->id) {
            throw new \DomainException(__('commissions.cannot_request_from_self'));
        }

        return DB::transaction(function () use ($buyer, $seller, $data): Commission {
            $commission = Commission::query()->create([
                'number' => 'CM-'.random_int(1000, 9999),
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'text' => $data['text'],
                'text_language' => $data['text_language'] ?? 'ar',
                'script_id' => $data['script_id'] ?? null,
                'medium_id' => $data['medium_id'] ?? null,
                'width_cm' => $data['width_cm'] ?? null,
                'height_cm' => $data['height_cm'] ?? null,
                'message' => $data['message'] ?? null,
                'budget_minor' => $data['budget_minor'] ?? null,
                'deadline' => $data['deadline'] ?? null,
            ]);

            Notification::send(
                User::query()->whereKey($seller->id)->get(),
                new CommissionUpdate($commission, 'requested'),
            );

            return $commission;
        });
    }
}
