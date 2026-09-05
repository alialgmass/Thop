<?php

namespace Modules\Inquiries\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inquiries\Enums\ReportableType;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Models\Report;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reportable_type' => ReportableType::Inquiry->value,
            'reportable_id' => Inquiry::factory(),
            'reporter_id' => User::factory(),
            'reason' => fake()->sentence(),
        ];
    }
}
