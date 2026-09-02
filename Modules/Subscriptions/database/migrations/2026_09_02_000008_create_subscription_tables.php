<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('account_type'); // importer | wholesaler | retailer
            $table->string('name');
            $table->decimal('price', 10, 2)->nullable(); // "not specified in source" — nullable
            $table->string('billing_cycle')->nullable(); // monthly | annual
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscription_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('key'); // e.g. product_limit, inquiry_limit, search_priority
            $table->string('value'); // admin-editable, no migration needed to change caps
            $table->unique(['plan_id', 'key']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_account_id')->constrained('business_accounts');
            $table->foreignId('plan_id')->constrained('subscription_plans');
            $table->string('status')->default('active'); // active | expired | cancelled | restricted
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->json('notes')->nullable(); // pending_plan_id, cancel_at_period_end
            $table->timestamps();
        });

        Schema::create('subscription_usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('key'); // e.g. product_count, inquiry_count
            $table->unsignedBigInteger('current_value')->default(0);
            $table->unique(['subscription_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usage_counters');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_entitlements');
        Schema::dropIfExists('subscription_plans');
    }
};
