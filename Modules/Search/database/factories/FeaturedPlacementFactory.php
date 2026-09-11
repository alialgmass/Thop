<?php

namespace Modules\Search\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Search\Models\FeaturedPlacement;

/**
 * @extends Factory<FeaturedPlacement>
 */
class FeaturedPlacementFactory extends Factory
{
    protected $model = FeaturedPlacement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'featurable_type' => 'product',
            'featurable_id' => Product::factory(),
            'slot' => 'homepage_top',
            'starts_at' => null,
            'ends_at' => null,
            'created_by' => User::factory()->admin(),
        ];
    }

    public function forSupplier(): static
    {
        return $this->state(fn (): array => [
            'featurable_type' => 'supplier',
            'featurable_id' => BusinessAccount::factory(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function notYetStarted(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->addDay(),
        ]);
    }
}
