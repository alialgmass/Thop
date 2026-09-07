<?php

namespace Modules\Chat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\Message;
use Modules\Inquiries\Models\Inquiry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $sellerUser;

    private BusinessAccount $sellerBusiness;

    private Inquiry $inquiry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->wholesaler()->create();
        $this->sellerUser = User::factory()->importer()->create();
        $this->sellerBusiness = BusinessAccount::factory()->for($this->sellerUser, 'owner')->create();

        $this->inquiry = Inquiry::factory()->create([
            'buyer_id' => $this->buyer->id,
            'seller_business_id' => $this->sellerBusiness->id,
        ]);
    }

    #[Test]
    public function either_party_opens_the_conversation_and_a_second_open_returns_the_same_one(): void
    {
        $first = $this->actingAs($this->buyer)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/conversation")
            ->assertCreated()
            ->json('body.conversation.id');

        $this->actingAs($this->sellerUser)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/conversation")
            ->assertOk()
            ->assertJsonPath('body.conversation.id', $first);

        $this->assertSame(1, Conversation::where('inquiry_id', $this->inquiry->id)->count());
    }

    #[Test]
    public function a_stranger_cannot_open_or_view_the_conversation(): void
    {
        $stranger = User::factory()->wholesaler()->create();
        $conversation = Conversation::factory()->forInquiry($this->inquiry)->create();

        $this->actingAs($stranger)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/conversation")
            ->assertForbidden();

        $this->actingAs($stranger)
            ->getJson("/api/v1/conversations/{$conversation->id}")
            ->assertForbidden();
    }

    #[Test]
    public function the_conversations_list_carries_per_thread_and_total_unread_counts(): void
    {
        $conversation = Conversation::factory()->forInquiry($this->inquiry)->create();
        Message::factory()->count(3)->for($conversation)->create(['sender_id' => $this->sellerUser->id]);
        Message::factory()->for($conversation)->create(['sender_id' => $this->buyer->id]);

        $this->actingAs($this->buyer)
            ->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('body.total_unread', 3)
            ->assertJsonPath('body.conversations.0.unread_count', 3);
    }

    #[Test]
    public function opening_a_conversation_marks_the_other_partys_messages_read(): void
    {
        $conversation = Conversation::factory()->forInquiry($this->inquiry)->create();
        Message::factory()->count(2)->for($conversation)->create(['sender_id' => $this->sellerUser->id]);

        $this->actingAs($this->buyer)
            ->postJson("/api/v1/conversations/{$conversation->id}/read")
            ->assertOk()
            ->assertJsonPath('body.unread_count', 0)
            ->assertJsonPath('body.total_unread', 0);

        $this->assertSame(0, $conversation->fresh()->unreadCountFor($this->buyer));
        $this->assertSame(2, Message::whereNotNull('read_at')->count());
    }

    #[Test]
    public function my_own_messages_never_count_as_unread_to_me(): void
    {
        $conversation = Conversation::factory()->forInquiry($this->inquiry)->create();
        Message::factory()->count(4)->for($conversation)->create(['sender_id' => $this->buyer->id]);

        $this->actingAs($this->buyer)
            ->getJson('/api/v1/conversations')
            ->assertJsonPath('body.total_unread', 0);
    }
}
