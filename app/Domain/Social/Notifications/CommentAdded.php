<?php

namespace App\Domain\Social\Notifications;

use App\Domain\Social\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CommentAdded extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Comment $comment,
        public readonly string $context, // 'piece' or 'reply'
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $line = $this->context === 'reply'
            ? __('social.notif_reply_line', ['name' => $this->actorName()])
            : __('social.notif_comment_line', ['name' => $this->actorName()]);

        return (new MailMessage)
            ->subject(__('social.notif_comment_subject'))
            ->greeting(__('social.hello', ['name' => self::nameOf($notifiable)]))
            ->line($line)
            ->line('“'.$this->comment->body.'”')
            ->action(__('social.view_piece'), route('pieces.show', $this->comment->piece_id));
    }

    /** @return array<string, string|int> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'comment',
            'context' => $this->context,
            'comment_id' => (int) $this->comment->id,
            'piece_id' => (int) $this->comment->piece_id,
            'actor_name' => $this->actorName(),
            'excerpt' => mb_substr($this->comment->body, 0, 80),
        ];
    }

    private function actorName(): string
    {
        return self::nameOf($this->comment->user);
    }

    private static function nameOf(mixed $entity): string
    {
        return $entity instanceof User ? $entity->name : '';
    }
}
