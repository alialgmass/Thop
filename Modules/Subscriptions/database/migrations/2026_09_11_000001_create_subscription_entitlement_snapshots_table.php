<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9 · T4 (US-SUB-05 non-retroactive plan edits, issue #33).
 *
 * `subscription_entitlements` is plan-scoped only — before this migration,
 * EntitlementService read a subscription's capabilities straight off its
 * plan, live, every time. That means editing a plan's entitlements already
 * took effect instantly and retroactively for every existing subscriber on
 * it, with no way to make an edit "non-retroactive by default" and no
 * separate action for admin to force-apply a change to existing
 * subscriptions — both of which the spec requires.
 *
 * This table is that missing snapshot: one copy of a subscription's
 * entitlements, taken when the subscription starts (or switches plan) and
 * left alone after that unless an admin explicitly re-applies the plan's
 * current entitlements (`ManageSubscriptionPlan::applyToExisting()`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_entitlement_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('key');
            $table->string('value');
            $table->unique(['subscription_id', 'key']);
        });

        // Backfill: every subscription that already exists has never had a
        // snapshot taken, so seed one from its current plan now. Safe to do
        // unconditionally — this table is new, so every row here is a fresh
        // insert, never an overwrite of an admin's prior decision.
        $rows = DB::table('subscriptions')
            ->join('subscription_entitlements', 'subscription_entitlements.plan_id', '=', 'subscriptions.plan_id')
            ->select('subscriptions.id as subscription_id', 'subscription_entitlements.key', 'subscription_entitlements.value')
            ->get();

        foreach ($rows->chunk(500) as $chunk) {
            DB::table('subscription_entitlement_snapshots')->insert(
                $chunk->map(fn ($row): array => [
                    'subscription_id' => $row->subscription_id,
                    'key' => $row->key,
                    'value' => $row->value,
                ])->all(),
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_entitlement_snapshots');
    }
};
