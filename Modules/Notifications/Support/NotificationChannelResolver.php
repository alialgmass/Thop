<?php

namespace Modules\Notifications\Support;

use App\Models\User;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Models\NotificationPreference;
use Modules\Notifications\Notifications\BaseNotification;

/**
 * The single server-side decision point for which channels a notification
 * goes out on (US-NOT-20). Never accepts a caller-supplied channel list.
 *
 * The rule:
 *   1. Start from the notification's Notification-Matrix (§14) default channels.
 *   2. `database` is always kept — the in-app feed is not opt-out (US-NOT-01).
 *   3. For an operational notification, `mail`/`sms` are forced on regardless
 *      of preference — account/financial events must not be missed (US-NOT-03).
 *   4. Every other channel is kept only if the user's `(category, channel)`
 *      preference is enabled (default per category — marketing off, rest on).
 */
class NotificationChannelResolver
{
    /**
     * @return array<int, string> channel identifiers for the notification's `via()`
     */
    public function resolve(BaseNotification $notification, User $notifiable): array
    {
        $category = $notification->category();
        $operational = $notification->operational();

        $channels = [];

        foreach ($notification->matrixChannels() as $channel) {
            $keep = match (true) {
                $channel === NotificationChannel::Database => true,
                $operational && $channel->isOperationalOverride() => true,
                default => NotificationPreference::resolveEnabled($notifiable, $category, $channel),
            };

            if ($keep) {
                $channels[] = $channel->driver();
            }
        }

        return array_values(array_unique($channels));
    }
}
