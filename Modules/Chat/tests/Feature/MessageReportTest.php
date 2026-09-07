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

class MessageReportTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $sellerUser;

    private Conversation $conversation;

    private Message $message;

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
        $this->message = Message::factory()->for($this->conversation)->create(['sender_id' => $this->sellerUser->id]);
    }

    #[Test]
    public function either_party_can_report_a_message_and_it_is_recorded(): void
    {
        $this->actingAs($this->buyer)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages/{$this->message->id}/reports", [
                'reason' => 'Abusive language.',
            ])
            ->assertCreated()
            ->assertJsonPath('body.report.reportable_type', 'message');

        $this->assertDatabaseHas('reports', [
            'reportable_type' => 'message',
            'reportable_id' => $this->message->id,
            'reporter_id' => $this->buyer->id,
            'reason' => 'Abusive language.',
        ]);
    }

    #[Test]
    public function the_seller_can_also_report_a_message(): void
    {
        $buyerMessage = Message::factory()->for($this->conversation)->create(['sender_id' => $this->buyer->id]);

        $this->actingAs($this->sellerUser)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages/{$buyerMessage->id}/reports", [
                'reason' => 'Spam.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('reports', [
            'reportable_type' => 'message',
            'reportable_id' => $buyerMessage->id,
            'reporter_id' => $this->sellerUser->id,
        ]);
    }

    #[Test]
    public function a_non_participant_cannot_report_a_message(): void
    {
        $stranger = User::factory()->wholesaler()->create();

        $this->actingAs($stranger)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages/{$this->message->id}/reports", [
                'reason' => 'nope',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('reports', 0);
    }

    #[Test]
    public function a_message_from_another_conversation_cannot_be_reported_through_this_one(): void
    {
        $otherConversation = Conversation::factory()->create();
        $otherMessage = Message::factory()->for($otherConversation)->create();

        $this->actingAs($this->buyer)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages/{$otherMessage->id}/reports", [
                'reason' => 'mismatch',
            ])
            ->assertNotFound();
    }
}
