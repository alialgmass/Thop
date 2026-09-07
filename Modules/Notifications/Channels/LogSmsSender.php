<?php

namespace Modules\Notifications\Channels;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Contracts\SmsSender;

/**
 * Default SMS driver — writes the message to the log. A real gateway is a
 * config-driven binding swap (SI-FR-04).
 */
class LogSmsSender implements SmsSender
{
    public function send(User $notifiable, string $message): void
    {
        Log::channel(config('notifications.sms.log_channel', 'stack'))->info('sms notification', [
            'user_id' => $notifiable->getKey(),
            'phone' => $notifiable->phone,
            'message' => $message,
        ]);
    }
}
