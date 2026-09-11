<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9 · T4 (issue #33): admin-editable trial length per plan. Nullable —
 * no plan currently has a defined trial length; `POST /subscriptions` still
 * accepts a client-supplied `trial_ends_at` regardless (unchanged, out of
 * this ticket's scope — the acceptance criteria only ask that an admin can
 * edit this attribute on the plan, not that subscribing consumes it yet).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->unsignedSmallInteger('trial_days')->nullable()->after('billing_cycle');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->dropColumn('trial_days');
        });
    }
};
