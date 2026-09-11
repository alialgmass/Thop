<?php

namespace Modules\Subscriptions\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Models\AuditLog;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Subscriptions\Support\SyncsEntitlementRows;

/**
 * The single place an admin create/edit/force-apply on a subscription plan
 * is applied (US-SUB-05, US-ADM-04, Phase 9 · T4). REST parity with the
 * Filament panel's plain fields — the panel's entitlements Repeater still
 * saves through its own `->relationship()` wiring, not through here, but
 * every Save still produces exactly one `plan.updated` audit row per edit
 * (see `Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages\EditSubscriptionPlan`).
 *
 * A plan edit here never touches any existing subscription's entitlement
 * snapshot — that's the whole point of {@see self::applyToExisting()}
 * existing as a separate, explicit action.
 */
class ManageSubscriptionPlan
{
    /**
     * @param  array{account_type?: string, name?: string, price?: string|float|null, billing_cycle?: string|null, trial_days?: int|null, is_active?: bool, entitlements?: list<array{key: string, value: string}>}  $data
     */
    public function create(array $data, User $admin): SubscriptionPlan
    {
        $plan = DB::transaction(function () use ($data, $admin): SubscriptionPlan {
            $plan = SubscriptionPlan::create($this->planFields($data));

            $this->syncEntitlements($plan, $data['entitlements'] ?? []);

            AuditLog::record($admin, AuditAction::SubscriptionPlanCreated, $plan, [
                'name' => $plan->name,
            ]);

            return $plan;
        });

        return $plan;
    }

    /**
     * @param  array{account_type?: string, name?: string, price?: string|float|null, billing_cycle?: string|null, trial_days?: int|null, is_active?: bool, entitlements?: list<array{key: string, value: string}>}  $data
     */
    public function update(SubscriptionPlan $plan, array $data, User $admin): SubscriptionPlan
    {
        DB::transaction(function () use ($plan, $data, $admin): void {
            $plan->forceFill($this->planFields($data))->save();

            if (array_key_exists('entitlements', $data)) {
                $this->syncEntitlements($plan, $data['entitlements']);
            }

            AuditLog::record($admin, AuditAction::SubscriptionPlanUpdated, $plan, [
                'name' => $plan->name,
            ]);
        });

        return $plan;
    }

    /**
     * Force-apply this plan's *current* entitlements onto every one of its
     * active subscriptions' snapshots, right now. The only way an existing
     * subscriber is retroactively affected by a plan edit (US-SUB-05).
     *
     * Scoped with {@see Subscription::scopeCurrentlyActive()} — the same
     * "really active" definition BR-SUB-03's product-visibility gate uses —
     * rather than a bare `status = active` check, so a subscription whose
     * paid period has lapsed but hasn't been processed yet by
     * `subscriptions:process-period-ends` isn't force-applied.
     *
     * @return int the number of subscriptions updated
     */
    public function applyToExisting(SubscriptionPlan $plan, User $admin): int
    {
        $count = 0;

        DB::transaction(function () use ($plan, $admin, &$count): void {
            Subscription::query()
                ->where('plan_id', $plan->getKey())
                ->currentlyActive()
                ->with('plan.entitlements')
                ->each(function (Subscription $subscription) use (&$count): void {
                    $subscription->syncEntitlementsFromPlan();
                    $count++;
                });

            AuditLog::record($admin, AuditAction::SubscriptionPlanAppliedToExisting, $plan, [
                'name' => $plan->name,
                'subscriptions_updated' => $count,
            ]);
        });

        return $count;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function planFields(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'account_type', 'name', 'price', 'billing_cycle', 'trial_days', 'is_active',
        ]));
    }

    /**
     * @param  list<array{key: string, value: string}>  $entitlements
     */
    private function syncEntitlements(SubscriptionPlan $plan, array $entitlements): void
    {
        SyncsEntitlementRows::sync($plan->entitlements(), array_column($entitlements, 'value', 'key'));
    }
}
