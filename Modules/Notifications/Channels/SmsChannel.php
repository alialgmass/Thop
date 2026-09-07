<?php

namespace Modules\Notifications\Channels;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Contracts\SmsSender;

/**
 * Laravel notification channel for SMS. A notification opts in by defining
 * `toSms($notifiable): string`; without it, nothing is sent on this channel.
 */
class SmsChannel
{
    public function __construct(private readonly SmsSender $sender) {}

    public function send(User $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if ($message !== '') {
            $this->sender->send($notifiable, $message);
        }
    }
}
