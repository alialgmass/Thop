<?php

namespace Modules\Inquiries\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Inquiries\Enums\LeadStatus;
use Modules\Inquiries\Models\Inquiry;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buyer_id' => User::factory()->wholesaler(),
            'seller_business_id' => BusinessAccount::factory(),
            'product_id' => null,
            'message' => fake()->sentence(),
            'lead_status' => LeadStatus::New,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(['lead_status' => LeadStatus::InProgress]);
    }

    public function done(): static
    {
        return $this->state(['lead_status' => LeadStatus::Done]);
    }

    public function notCompleted(): static
    {
        return $this->state(['lead_status' => LeadStatus::NotCompleted]);
    }
}
