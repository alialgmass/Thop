<?php

namespace Modules\Subscriptions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscriptions\Models\SubscriptionPlan;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_type' => 'importer',
            'name' => fake()->unique()->word(),
            'price' => null,
            'billing_cycle' => null,
            'is_active' => true,
        ];
    }

    public function importer(): static
    {
        return $this->state(fn () => ['account_type' => 'importer']);
    }

    public function wholesaler(): static
    {
        return $this->state(fn () => ['account_type' => 'wholesaler']);
    }

    public function retailer(): static
    {
        return $this->state(fn () => ['account_type' => 'retailer']);
    }
}
