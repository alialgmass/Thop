<?php

namespace Modules\Admin\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Admin\Models\Banner;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_disk' => 'public',
            'image_path' => 'banners/'.fake()->uuid().'.jpg',
            'link_url' => fake()->optional()->url(),
            'position' => fake()->numberBetween(0, 10),
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
            'created_by' => User::factory()->admin(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
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
        return $this->state(fn (): array => ['starts_at' => now()->addDay()]);
    }
}
