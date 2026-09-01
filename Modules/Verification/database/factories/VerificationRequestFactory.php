<?php

namespace Modules\Verification\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Models\VerificationRequest;

/**
 * @extends Factory<VerificationRequest>
 */
class VerificationRequestFactory extends Factory
{
    protected $model = VerificationRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_account_id' => BusinessAccount::factory(),
            'status' => VerificationRequestStatus::Pending,
            'submitted_at' => now(),
        ];
    }

    public function unsubmitted(): static
    {
        return $this->state(['submitted_at' => null]);
    }
}
