<?php

namespace Modules\Inquiries\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Inquiries\Enums\LeadStatus;
use Modules\Inquiries\Models\Inquiry;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $sellerUser;

    private BusinessAccount $sellerBusiness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->wholesaler()->create();

        $this->sellerUser = User::factory()->importer()->create();
        $this->sellerBusiness = BusinessAccount::factory()
            ->for($this->sellerUser, 'owner')
            ->create();

        $this->givenSellerInquiryLimit(30);
    }

    private function givenSellerInquiryLimit(int $limit): void
    {
        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'inquiry_limit', 'value' => (string) $limit]);

        Subscription::create([
            'business_account_id' => $this->sellerBusiness->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }

    #[Test]
    public function a_buyer_sends_an_inquiry_about_a_product(): void
    {
        $product = Product::factory()->for($this->sellerBusiness, 'businessAccount')->create();

        $this->actingAs($this->buyer)
            ->postJson('/api/v1/inquiries', [
                'product_id' => $product->id,
                'message' => 'Interested in this fabric, can you share more details?',
            ])
            ->assertCreated()
            ->assertJsonPath('body.inquiry.seller_business_id', $this->sellerBusiness->id)
            ->assertJsonPath('body.inquiry.product_id', $product->id)
            ->assertJsonPath('body.inquiry.lead_status', LeadStatus::New->value);

        $this->assertDatabaseHas('inquiries', [
            'buyer_id' => $this->buyer->id,
            'seller_business_id' => $this->sellerBusiness->id,
            'product_id' => $product->id,
            'lead_status' => LeadStatus::New->value,
        ]);
    }

    #[Test]
    public function a_buyer_sends_a_general_inquiry_to_a_supplier_with_no_product(): void
    {
        $this->actingAs($this->buyer)
            ->postJson('/api/v1/inquiries', [
                'seller_business_id' => $this->sellerBusiness->id,
                'message' => 'Do you carry cotton twill?',
            ])
            ->assertCreated()
            ->assertJsonPath('body.inquiry.product_id', null);
    }

    #[Test]
    public function a_new_inquiry_is_a_lead_in_new_status(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/v1/inquiries', [
                'seller_business_id' => $this->sellerBusiness->id,
                'message' => 'Hello',
            ])
            ->assertCreated();

        $this->assertSame('new', $response->json('body.inquiry.lead_status'));
    }

    #[Test]
    public function sending_to_a_seller_past_their_inquiry_limit_is_rejected_clearly(): void
    {
        // Replace the seller's plan with a zero-limit one instead of a silent drop.
        SubscriptionEntitlement::where('key', 'inquiry_limit')->update(['value' => '0']);

        $response = $this->actingAs($this->buyer)
            ->postJson('/api/v1/inquiries', [
                'seller_business_id' => $this->sellerBusiness->id,
                'message' => 'Hello',
            ])
            ->assertStatus(422);

        $this->assertNotEmpty($response->json('body.inquiry_limit'));
        $this->assertDatabaseCount('inquiries', 0);
    }

    #[Test]
    public function missing_target_and_message_are_validation_errors(): void
    {
        $this->actingAs($this->buyer)
            ->postJson('/api/v1/inquiries', [])
            ->assertStatus(400);
    }

    #[Test]
    public function a_mismatched_product_and_seller_business_is_rejected(): void
    {
        $otherBusiness = BusinessAccount::factory()->create();
        $product = Product::factory()->for($this->sellerBusiness, 'businessAccount')->create();

        $this->actingAs($this->buyer)
            ->postJson('/api/v1/inquiries', [
                'product_id' => $product->id,
                'seller_business_id' => $otherBusiness->id,
                'message' => 'Hello',
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function an_importer_cannot_send_an_inquiry(): void
    {
        $importer = User::factory()->importer()->create();

        $this->actingAs($importer)
            ->postJson('/api/v1/inquiries', [
                'seller_business_id' => $this->sellerBusiness->id,
                'message' => 'Hello',
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function the_seller_sees_only_their_own_leads(): void
    {
        $mine = Inquiry::factory()->create(['seller_business_id' => $this->sellerBusiness->id]);
        Inquiry::factory()->create(); // another seller's lead

        $this->actingAs($this->sellerUser)
            ->getJson('/api/v1/inquiries?role=seller')
            ->assertOk()
            ->assertJsonCount(1, 'body.inquiries.data')
            ->assertJsonPath('body.inquiries.data.0.id', $mine->id);
    }

    #[Test]
    public function the_seller_can_filter_leads_by_status(): void
    {
        Inquiry::factory()->create(['seller_business_id' => $this->sellerBusiness->id, 'lead_status' => LeadStatus::New]);
        Inquiry::factory()->done()->create(['seller_business_id' => $this->sellerBusiness->id]);

        $this->actingAs($this->sellerUser)
            ->getJson('/api/v1/inquiries?role=seller&lead_status=done')
            ->assertOk()
            ->assertJsonCount(1, 'body.inquiries.data')
            ->assertJsonPath('body.inquiries.data.0.lead_status', 'done');
    }

    #[Test]
    public function a_buyer_sees_their_own_sent_inquiries(): void
    {
        $mine = Inquiry::factory()->create(['buyer_id' => $this->buyer->id]);
        Inquiry::factory()->create(); // someone else's inquiry

        $this->actingAs($this->buyer)
            ->getJson('/api/v1/inquiries?role=buyer')
            ->assertOk()
            ->assertJsonCount(1, 'body.inquiries.data')
            ->assertJsonPath('body.inquiries.data.0.id', $mine->id);
    }

    #[Test]
    public function a_non_party_cannot_view_an_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create();
        $stranger = User::factory()->wholesaler()->create();

        $this->actingAs($stranger)
            ->getJson("/api/v1/inquiries/{$inquiry->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function a_different_sellers_business_owner_cannot_view_someone_elses_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create(['seller_business_id' => $this->sellerBusiness->id]);

        $otherSellerUser = User::factory()->importer()->create();
        BusinessAccount::factory()->for($otherSellerUser, 'owner')->create();

        $this->actingAs($otherSellerUser)
            ->getJson("/api/v1/inquiries/{$inquiry->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function the_seller_moves_a_lead_through_every_status(): void
    {
        $inquiry = Inquiry::factory()->create(['seller_business_id' => $this->sellerBusiness->id]);

        foreach ([LeadStatus::InProgress, LeadStatus::Done, LeadStatus::NotCompleted] as $status) {
            $this->actingAs($this->sellerUser)
                ->patchJson("/api/v1/inquiries/{$inquiry->id}", ['lead_status' => $status->value])
                ->assertOk()
                ->assertJsonPath('body.inquiry.lead_status', $status->value);
        }
    }

    #[Test]
    public function an_unknown_lead_status_is_a_validation_error(): void
    {
        $inquiry = Inquiry::factory()->create(['seller_business_id' => $this->sellerBusiness->id]);

        $this->actingAs($this->sellerUser)
            ->patchJson("/api/v1/inquiries/{$inquiry->id}", ['lead_status' => 'archived'])
            ->assertStatus(400);
    }

    #[Test]
    public function a_buyer_cannot_change_lead_status(): void
    {
        $inquiry = Inquiry::factory()->create(['buyer_id' => $this->buyer->id, 'seller_business_id' => $this->sellerBusiness->id]);

        $this->actingAs($this->buyer)
            ->patchJson("/api/v1/inquiries/{$inquiry->id}", ['lead_status' => 'done'])
            ->assertStatus(403);
    }

    #[Test]
    public function inquiry_creation_is_rate_limited(): void
    {
        config(['inquiries.throttle.create_per_minute' => 1]);

        $payload = ['seller_business_id' => $this->sellerBusiness->id, 'message' => 'Hello'];

        $this->actingAs($this->buyer)->postJson('/api/v1/inquiries', $payload)->assertCreated();
        $this->actingAs($this->buyer)->postJson('/api/v1/inquiries', $payload)->assertStatus(429);
    }

    #[Test]
    public function guests_cannot_use_inquiries(): void
    {
        $this->getJson('/api/v1/inquiries')->assertStatus(401);
    }
}
