<?php

namespace Modules\Chat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Chat\Models\Conversation;
use Modules\Inquiries\Models\Inquiry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The Pusher private-channel auth callback is the real enforcement point for
 * realtime access (US-CHT-02) — it must run the same ConversationPolicy as
 * the REST endpoints, never trust the client-declared channel name.
 *
 * The null/log broadcasters no-op channel authorization, so this class runs
 * against the pusher driver with dummy credentials (auth signing is local
 * HMAC — no network). Env is set before the app boots so the channel
 * definitions register on the pusher broadcaster instance.
 */
class BroadcastChannelAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    private array $pusherEnv = [
        'BROADCAST_CONNECTION' => 'pusher',
        'PUSHER_APP_KEY' => 'test-key',
        'PUSHER_APP_SECRET' => 'test-secret',
        'PUSHER_APP_ID' => 'test-app',
        'PUSHER_APP_CLUSTER' => 'mt1',
    ];

    protected function setUp(): void
    {
        foreach ($this->pusherEnv as $key => $value) {
            $_ENV[$key] = $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach (array_keys($this->pusherEnv) as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        parent::tearDown();
    }

    private function makeConversation(): Conversation
    {
        $buyer = User::factory()->wholesaler()->create();
        $sellerUser = User::factory()->importer()->create();
        $sellerBusiness = BusinessAccount::factory()->for($sellerUser, 'owner')->create();

        $inquiry = Inquiry::factory()->create([
            'buyer_id' => $buyer->id,
            'seller_business_id' => $sellerBusiness->id,
        ]);

        return Conversation::factory()->forInquiry($inquiry)->create();
    }

    #[Test]
    public function a_non_participant_is_refused_the_private_conversation_channel(): void
    {
        $conversation = $this->makeConversation();
        $stranger = User::factory()->wholesaler()->create();

        $this->actingAs($stranger)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.1234',
                'channel_name' => "private-conversation.{$conversation->id}",
            ])
            ->assertForbidden();
    }

    #[Test]
    public function the_buyer_is_granted_the_private_conversation_channel(): void
    {
        $conversation = $this->makeConversation();

        $this->actingAs($conversation->buyer)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.1234',
                'channel_name' => "private-conversation.{$conversation->id}",
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    #[Test]
    public function the_seller_business_owner_is_granted_the_private_conversation_channel(): void
    {
        $conversation = $this->makeConversation();

        $this->actingAs($conversation->sellerBusiness->owner)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.1234',
                'channel_name' => "private-conversation.{$conversation->id}",
            ])
            ->assertOk();
    }
}
