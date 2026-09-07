<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Conversation;
use Modules\Inquiries\Models\Inquiry;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inquiry = Inquiry::factory()->create();

        return [
            'inquiry_id' => $inquiry->id,
            'buyer_id' => $inquiry->buyer_id,
            'seller_business_id' => $inquiry->seller_business_id,
        ];
    }

    /**
     * Build the conversation on top of an existing inquiry, keeping the
     * buyer / seller-business columns in sync with it.
     */
    public function forInquiry(Inquiry $inquiry): static
    {
        return $this->state([
            'inquiry_id' => $inquiry->id,
            'buyer_id' => $inquiry->buyer_id,
            'seller_business_id' => $inquiry->seller_business_id,
        ]);
    }
}
