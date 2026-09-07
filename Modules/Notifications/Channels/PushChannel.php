<?php

namespace Modules\Notifications\Channels;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Contracts\PushSender;

/**
 * Laravel notification channel for push. Delegates the actual transport to
 * the bound {@see PushSender} seam. A notification opts in by defining
 * `toPush($notifiable): array`; otherwise its `toArray()` payload is used.
 */
class PushChannel
{
    public function __construct(private readonly PushSender $sender) {}

    public function send(User $notifiable, Notification $notification): void
    {
        /** @var array<string, mixed> $payload */
        $payload = method_exists($notification, 'toPush')
            ? $notification->toPush($notifiable)
            : $notification->toArray($notifiable);

        $this->sender->send($notifiable, $payload);
    }
}
