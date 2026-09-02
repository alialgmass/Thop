<?php

namespace Modules\Subscriptions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionPlan;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_account_id' => BusinessAccount::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
            'trial_ends_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'restricted',
            'current_period_end' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }

    public function trial(): static
    {
        return $this->state(fn () => [
            'trial_ends_at' => now()->addDays(14),
        ]);
    }
}
