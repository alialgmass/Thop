<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\Message;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $conversation = Conversation::factory()->create();

        return [
            'conversation_id' => $conversation->id,
            'sender_id' => $conversation->buyer_id,
            'body' => fake()->sentence(),
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(['read_at' => now()]);
    }
}
