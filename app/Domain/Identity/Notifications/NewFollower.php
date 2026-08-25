<?php

namespace App\Domain\Identity\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewFollower extends Notification
{
    use Queueable;

    public function __construct(public readonly User $follower) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('social.notif_follow_subject'))
            ->greeting(__('social.hello', ['name' => self::nameOf($notifiable)]))
            ->line(__('social.notif_follow_line', ['name' => $this->follower->name]))
            ->action(__('social.view_profile'), $this->follower->profileUrl());
    }

    /** @return array<string, string|int> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'follow',
            'follower_id' => $this->follower->id,
            'actor_name' => $this->follower->name,
            'username' => (string) $this->follower->username,
        ];
    }

    private static function nameOf(mixed $entity): string
    {
        return $entity instanceof User ? $entity->name : '';
    }
}
