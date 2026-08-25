<?php

namespace App\Domain\Commissions\Notifications;

use App\Domain\Commissions\Models\Commission;
use App\Models\User;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification class per audience event, keyed by context so the
 * lifecycle stays data-driven instead of spawning a class per transition.
 */
final class CommissionUpdate extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Commission $commission,
        public readonly string $context,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('commissions.notif_subject'))
            ->greeting(__('social.hello', ['name' => self::nameOf($notifiable)]))
            ->line(__('commissions.context_'.$this->context.'_line', ['number' => $this->commission->number]))
            ->action(__('commissions.open_workspace'), route('commissions.show', $this->commission));
    }

    /** @return array<string, string|int> */
    public function toArray(object $notifiable): array
    {
        $amount = $this->commission->quote_minor !== null
            ? Money::format($this->commission->quote_minor).' '.$this->commission->currency
            : '';

        return [
            'type' => 'commission',
            'context' => $this->context,
            'commission_id' => (int) $this->commission->id,
            'number' => $this->commission->number,
            'amount' => $amount,
        ];
    }

    private static function nameOf(mixed $entity): string
    {
        return $entity instanceof User ? $entity->name : '';
    }
}
