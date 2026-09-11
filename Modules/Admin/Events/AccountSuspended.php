<?php

namespace Modules\Admin\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when an admin suspends a user account (US-ADM-07, Phase 9 · T8).
 *
 * NOT YET wired into `Modules\Notifications\Listeners\NotificationEventSubscriber` —
 * the ticket only asks that this be dispatched; deciding the notification's
 * channels/copy/recipient (there's no existing Notification Matrix row for
 * "account suspended") is a follow-up, not invented here.
 */
class AccountSuspended
{
    use Dispatchable;

    public function __construct(public readonly User $user) {}
}
