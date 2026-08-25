<?php

namespace App\Domain\Payments\Notifications;

use App\Domain\Orders\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderPaid extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly string $audience, // 'buyer' or 'seller'
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = Money::format($this->order->total_minor).' '.$this->order->currency;

        return (new MailMessage)
            ->subject(__('commerce.notif_paid_subject'))
            ->greeting(__('social.hello', ['name' => self::nameOf($notifiable)]))
            ->line(__('commerce.notif_paid_line_'.$this->audience, ['number' => '#'.$this->order->id, 'total' => $total]))
            ->action(__('commerce.view_order'), route('orders.show', $this->order));
    }

    /** @return array<string, string|int> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_paid',
            'audience' => $this->audience,
            'order_id' => (int) $this->order->id,
            'total_minor' => (int) $this->order->total_minor,
            'currency' => $this->order->currency,
        ];
    }

    private static function nameOf(mixed $entity): string
    {
        return $entity instanceof User ? $entity->name : '';
    }
}
