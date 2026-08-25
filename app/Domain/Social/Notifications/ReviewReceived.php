<?php

namespace App\Domain\Social\Notifications;

use App\Domain\Social\Models\Review;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ReviewReceived extends Notification
{
    use Queueable;

    public function __construct(public readonly Review $review) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('reviews.notif_subject'))
            ->greeting(__('social.hello', ['name' => self::nameOf($notifiable)]))
            ->line(__('reviews.notif_line', [
                'name' => self::nameOf($this->review->buyer),
                'rating' => str_repeat('★', $this->review->rating),
            ]))
            ->action(__('commissions.open_workspace'), route('commissions.show', $this->review->commission));
    }

    /** @return array<string, string|int> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review',
            'review_id' => (int) $this->review->id,
            'rating' => (int) $this->review->rating,
            'actor_name' => self::nameOf($this->review->buyer),
        ];
    }

    private static function nameOf(mixed $entity): string
    {
        return $entity instanceof User ? $entity->name : '';
    }
}
