<?php

namespace Modules\Notifications\Channels;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Contracts\PushSender;

/**
 * Default push driver — writes the payload to the log. Real provider
 * integration (SI-FR-04) is a follow-up ticket; this keeps the suite and
 * local dev free of an external dependency.
 */
class LogPushSender implements PushSender
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(User $notifiable, array $payload): void
    {
        Log::channel(config('notifications.push.log_channel', 'stack'))->info('push notification', [
            'user_id' => $notifiable->getKey(),
            'payload' => $payload,
        ]);
    }
}
