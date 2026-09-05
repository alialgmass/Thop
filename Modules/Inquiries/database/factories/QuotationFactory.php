<?php

namespace Modules\Inquiries\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inquiries\Models\Quotation;
use Modules\Inquiries\Models\Rfq;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rfq_id' => Rfq::factory(),
            'price' => fake()->randomFloat(2, 10, 500),
            'availability_note' => fake()->sentence(),
            'valid_until' => now()->addWeek(),
        ];
    }

    public function expired(): static
    {
        return $this->state(['valid_until' => now()->subDay()]);
    }
}
