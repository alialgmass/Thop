<?php

namespace Modules\Businesses\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Taxonomy\Models\Governorate;

/**
 * @extends Factory<BusinessAccount>
 */
class BusinessAccountFactory extends Factory
{
    protected $model = BusinessAccount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->importer(),
            'company_name' => fake()->company(),
            'activity' => fake()->word().' trading',
            'governorate_id' => Governorate::factory(),
            'address' => fake()->address(),
            'contact_person' => fake()->name(),
            'contact_channels' => [
                ['type' => 'phone', 'value' => '+2010'.fake()->numerify('########')],
            ],
            'verification_status' => VerificationStatus::Unverified,
            'onboarded_by_admin' => false,
        ];
    }

    public function verified(): static
    {
        return $this->state(['verification_status' => VerificationStatus::Verified]);
    }

    public function pending(): static
    {
        return $this->state(['verification_status' => VerificationStatus::Pending]);
    }

    public function rejected(): static
    {
        return $this->state(['verification_status' => VerificationStatus::Rejected]);
    }
}
