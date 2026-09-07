<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Support\NotificationChannelResolver;

/**
 * Base for every notification this module sends. Always queued (US-NOT-22) so
 * a slow provider never blocks the request that triggered it. Channel
 * selection is delegated to {@see NotificationChannelResolver} — subclasses
 * declare only the category, the matrix defaults, and whether they're
 * operational.
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The preference category this notification belongs to (US-NOT-02).
     */
    abstract public function category(): NotificationCategory;

    /**
     * The channels the Notification Matrix (§14) prescribes for this event,
     * before preferences are applied.
     *
     * @return array<int, NotificationChannel>
     */
    abstract public function matrixChannels(): array;

    /**
     * Account/financial ("must not be missed") — forces mail/sms on
     * regardless of preference (US-NOT-03). Declared per notification: it is
     * finer-grained than the category (e.g. a verification *decision* to the
     * owner is operational, a verification *submitted* to the admin queue is
     * not). {@see NotificationCategory::isOperational()} is the coarse,
     * display-only counterpart used by the preferences grid.
     */
    public function operational(): bool
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    final public function via(User $notifiable): array
    {
        return app(NotificationChannelResolver::class)->resolve($this, $notifiable);
    }
}
