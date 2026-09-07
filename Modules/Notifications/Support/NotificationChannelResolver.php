<?php

namespace Modules\Notifications\Support;

use App\Models\User;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Models\NotificationPreference;
use Modules\Notifications\Notifications\BaseNotification;

/**
 * The single server-side decision point for which channels a notification
 * goes out on (US-NOT-20). Never accepts a caller-supplied channel list.
 *
 * The rule:
 *   1. Marketing is opt-in only and technically distinct — it ships on a
 *      channel only when an explicit `(marketing, channel)` preference is
 *      enabled, and never gets the database/operational treatment (US-NOT-04).
 *   2. Otherwise: start from the notification's Notification-Matrix (§14) channels.
 *   3. `database` is always kept — the in-app feed is not opt-out (US-NOT-01).
 *   4. For an operational notification, `mail`/`sms` are forced on regardless
 *      of preference — account/financial events must not be missed (US-NOT-03).
 *   5. Every other channel is kept only if the user's `(category, channel)`
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

        if ($category === NotificationCategory::Marketing) {
            return $this->marketingChannels($notification, $notifiable);
        }

        $selected = [];

        foreach ($notification->matrixChannels() as $channel) {
            $keep = match (true) {
                $channel === NotificationChannel::Database => true,
                $operational && $channel->isOperationalOverride() => true,
                default => NotificationPreference::resolveEnabled($notifiable, $category, $channel),
            };

            if ($keep) {
                $selected[$channel->value] = $channel;
            }
        }

        // An operational notification MUST reach the user on mail + sms
        // regardless of preference or whether the class listed them (US-NOT-03).
        if ($operational) {
            foreach ([NotificationChannel::Mail, NotificationChannel::Sms] as $forced) {
                $selected[$forced->value] = $forced;
            }
        }

        return array_values(array_map(
            fn (NotificationChannel $channel): string => $channel->driver(),
            $selected,
        ));
    }

    /**
     * Marketing: only channels the viewer explicitly opted in to, no database
     * fallback, no operational override (US-NOT-04). R1 ships no marketing
     * notification classes — this branch is the structure they will use.
     *
     * @return array<int, string>
     */
    private function marketingChannels(BaseNotification $notification, User $notifiable): array
    {
        $channels = [];

        foreach ($notification->matrixChannels() as $channel) {
            if (NotificationPreference::resolveEnabled($notifiable, NotificationCategory::Marketing, $channel)) {
                $channels[] = $channel->driver();
            }
        }

        return array_values(array_unique($channels));
    }
}
