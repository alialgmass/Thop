<?php

namespace Modules\Chat\Tests\Feature;

use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\Message;
use Modules\Inquiries\Models\Inquiry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $sellerUser;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->wholesaler()->create();
        $this->sellerUser = User::factory()->importer()->create();
        $sellerBusiness = BusinessAccount::factory()->for($this->sellerUser, 'owner')->create();

        $inquiry = Inquiry::factory()->create([
            'buyer_id' => $this->buyer->id,
            'seller_business_id' => $sellerBusiness->id,
        ]);

        $this->conversation = Conversation::factory()->forInquiry($inquiry)->create();
    }

    #[Test]
    public function a_participant_sends_a_message_and_it_is_persisted_and_returned(): void
    {
        $this->actingAs($this->buyer)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
                'body' => 'Is this fabric still available in navy?',
            ])
            ->assertCreated()
            ->assertJsonPath('body.message.body', 'Is this fabric still available in navy?')
            ->assertJsonPath('body.message.sender_id', $this->buyer->id);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->buyer->id,
            'body' => 'Is this fabric still available in navy?',
        ]);

        $this->actingAs($this->sellerUser)
            ->getJson("/api/v1/conversations/{$this->conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('body.messages.data.0.body', 'Is this fabric still available in navy?');
    }

    #[Test]
    public function a_stranger_cannot_send_a_message(): void
    {
        $stranger = User::factory()->wholesaler()->create();

        $this->actingAs($stranger)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", ['body' => 'hi'])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    #[Test]
    public function an_empty_or_over_long_message_is_rejected(): void
    {
        $this->actingAs($this->buyer)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", ['body' => ''])
            ->assertStatus(400);

        $this->actingAs($this->buyer)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
                'body' => str_repeat('a', (int) config('chat.message_max_length') + 1),
            ])
            ->assertStatus(400);
    }

    #[Test]
    public function sending_broadcasts_message_sent_on_the_private_conversation_channel(): void
    {
        Event::fake([MessageSent::class]);

        $this->actingAs($this->buyer)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", ['body' => 'hello'])
            ->assertCreated();

        Event::assertDispatched(MessageSent::class, function (MessageSent $event): bool {
            $channels = $event->broadcastOn();

            return $channels[0] instanceof PrivateChannel
                && $channels[0]->name === "private-conversation.{$this->conversation->id}"
                && $event->broadcastAs() === 'message.sent';
        });
    }

    #[Test]
    public function a_pusher_outage_never_fails_or_delays_the_send(): void
    {
        // The broadcast is queued (ShouldBroadcastAfterCommit) — the send path
        // returns before any broadcast work runs, so a Pusher/queue failure
        // cannot reach the caller. With the queue faked, the send still
        // succeeds and the message is durably persisted (US-CHT-05).
        Queue::fake();

        $this->actingAs($this->buyer)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", ['body' => 'still here?'])
            ->assertCreated();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'body' => 'still here?',
        ]);

        // The broadcast was handed off to the queue, not executed inline.
        Queue::assertPushed(BroadcastEvent::class);

        // And history still returns it without any broadcast having happened.
        $this->actingAs($this->buyer)
            ->getJson("/api/v1/conversations/{$this->conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('body.messages.data.0.body', 'still here?');
    }

    #[Test]
    public function message_history_is_paginated_not_returned_whole(): void
    {
        Message::factory()->count(70)->for($this->conversation)->create(['sender_id' => $this->sellerUser->id]);

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/v1/conversations/{$this->conversation->id}/messages")
            ->assertOk();

        $this->assertLessThanOrEqual(
            (int) config('chat.history_per_page'),
            count($response->json('body.messages.data')),
        );
        $this->assertNotNull($response->json('body.messages.meta.next_cursor'));
    }

    #[Test]
    public function sends_past_the_per_minute_threshold_are_throttled(): void
    {
        $limit = (int) config('chat.throttle.send_per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->actingAs($this->buyer)
                ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", ['body' => "msg {$i}"])
                ->assertCreated();
        }

        $this->actingAs($this->buyer)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", ['body' => 'one too many'])
            ->assertStatus(429);
    }
}
