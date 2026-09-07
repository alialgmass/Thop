<?php

namespace Modules\Subscriptions\Console;

use Illuminate\Console\Command;
use Modules\Subscriptions\Enums\SubscriptionStatus;
use Modules\Subscriptions\Events\SubscriptionExpiring;
use Modules\Subscriptions\Models\Subscription;

/**
 * Fires {@see SubscriptionExpiring} once per subscription whose paid period
 * ends within the reminder window (US-SUB-08 / US-NOT-01). Idempotent within
 * a period: a `notes.expiring_notified_for` marker stops a daily run from
 * re-notifying.
 *
 * Run daily via the scheduler: `subscriptions:notify-expiring`.
 */
class NotifyExpiringSubscriptions extends Command
{
    protected $signature = 'subscriptions:notify-expiring';

    protected $description = 'Notify sellers whose subscription period ends within the reminder window.';

    public function handle(): int
    {
        $windowDays = (int) config('subscriptions.expiring_reminder_days', 7);
        $count = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('current_period_end')
            ->whereBetween('current_period_end', [now(), now()->addDays($windowDays)])
            ->each(function (Subscription $subscription) use (&$count): void {
                $notes = json_decode($subscription->notes ?? '', true) ?: [];
                $marker = optional($subscription->current_period_end)->toDateString();

                if (($notes['expiring_notified_for'] ?? null) === $marker) {
                    return;
                }

                SubscriptionExpiring::dispatch($subscription);

                $notes['expiring_notified_for'] = $marker;
                $subscription->update(['notes' => json_encode($notes)]);

                $count++;
            });

        $this->info("Notified {$count} expiring subscription(s).");

        return self::SUCCESS;
    }
}
