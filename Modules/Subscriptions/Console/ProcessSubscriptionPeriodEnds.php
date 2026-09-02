<?php

namespace Modules\Subscriptions\Console;

use Illuminate\Console\Command;
use Modules\Subscriptions\Enums\SubscriptionStatus;
use Modules\Subscriptions\Models\Subscription;

/**
 * Applies deferred subscription changes at the end of the paid period
 * (BR-SUB-02) and marks expired subscriptions as restricted (BR-SUB-03).
 *
 * Run periodically (e.g. daily) via the scheduler:
 *   subscriptions:process-period-ends
 */
class ProcessSubscriptionPeriodEnds extends Command
{
    protected $signature = 'subscriptions:process-period-ends';

    protected $description = 'Apply pending downgrades/cancellations and expire subscriptions whose paid period or trial has ended.';

    public function handle(): int
    {
        $count = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', now())
            ->each(function (Subscription $subscription) use (&$count): void {
                $notes = json_decode($subscription->notes ?? '', true) ?: [];

                if (isset($notes['pending_plan_id'])) {
                    // Downgrade scheduled earlier: switch plan now and renew.
                    $subscription->update([
                        'plan_id' => (int) $notes['pending_plan_id'],
                        'notes' => $this->remainingNotes($notes, ['pending_plan_id']),
                        'current_period_end' => now()->addMonth(),
                    ]);
                } elseif (! empty($notes['cancel_at_period_end'])) {
                    // Cancellation effective at period end (BR-SUB-02).
                    $subscription->update([
                        'status' => SubscriptionStatus::Cancelled,
                        'notes' => $this->remainingNotes($notes, ['cancel_at_period_end']),
                    ]);
                } else {
                    // No pending action: the period simply lapsed (BR-SUB-03).
                    $subscription->markExpired();
                }

                $count++;
            });

        // Expire active trial subscriptions whose trial has ended (no paid period).
        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('trial_ends_at')
            ->whereNull('current_period_end')
            ->where('trial_ends_at', '<=', now())
            ->each(function (Subscription $subscription) use (&$count): void {
                $subscription->markExpired();
                $count++;
            });

        $this->info("Processed {$count} subscription(s) at period end.");

        return self::SUCCESS;
    }

    /**
     * Return the notes JSON with the given keys removed, or null when empty.
     */
    private function remainingNotes(array $notes, array $keys): ?string
    {
        foreach ($keys as $key) {
            unset($notes[$key]);
        }

        return $notes ? json_encode($notes) : null;
    }
}
