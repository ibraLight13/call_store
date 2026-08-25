<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\Models\CommissionMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class PostCommissionMessage
{
    public function handle(User $actor, Commission $commission, string $body): CommissionMessage
    {
        $body = trim($body);

        if ($body === '' || mb_strlen($body) > 2000) {
            throw new \InvalidArgumentException(__('commissions.message_invalid'));
        }

        return DB::transaction(function () use ($actor, $commission, $body): CommissionMessage {
            $message = CommissionMessage::query()->create([
                'commission_id' => $commission->id,
                'user_id' => $actor->id,
                'body' => $body,
            ]);

            $message->setRelation('user', $actor);

            $audience = (int) $actor->id === (int) $commission->buyer_id ? 'seller' : 'buyer';
            app(NotifyCounterparty::class)->handle($commission, $audience, 'messaged');

            return $message;
        });
    }
}
