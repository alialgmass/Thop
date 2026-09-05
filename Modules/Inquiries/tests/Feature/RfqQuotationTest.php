<?php

namespace Modules\Inquiries\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Models\Quotation;
use Modules\Inquiries\Models\Rfq;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RfqQuotationTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $sellerUser;

    private BusinessAccount $sellerBusiness;

    private Inquiry $inquiry;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->wholesaler()->create();

        $this->sellerUser = User::factory()->importer()->create();
        $this->sellerBusiness = BusinessAccount::factory()->for($this->sellerUser, 'owner')->create();

        $this->product = Product::factory()->for($this->sellerBusiness, 'businessAccount')->create(['moq' => 50]);

        $this->inquiry = Inquiry::factory()->create([
            'buyer_id' => $this->buyer->id,
            'seller_business_id' => $this->sellerBusiness->id,
            'product_id' => $this->product->id,
        ]);

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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validRfqPayload(array $overrides = []): array
    {
        return array_merge([
            'product_id' => $this->product->id,
            'quantity' => 100,
            'needed_by_date' => now()->addWeeks(2)->toDateString(),
        ], $overrides);
    }

    #[Test]
    public function a_buyer_submits_an_rfq_on_their_inquiry(): void
    {
        $this->actingAs($this->buyer)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/rfqs", $this->validRfqPayload())
            ->assertCreated()
            ->assertJsonPath('body.rfq.product_id', $this->product->id)
            ->assertJsonPath('body.rfq.quantity', 100)
            ->assertJsonPath('body.rfq.below_moq', false);

        $this->assertDatabaseHas('rfqs', [
            'inquiry_id' => $this->inquiry->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);
    }

    #[Test]
    public function missing_required_rfq_fields_are_rejected(): void
    {
        $this->actingAs($this->buyer)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/rfqs", [])
            ->assertStatus(400);
    }

    #[Test]
    public function a_quantity_below_moq_warns_but_still_succeeds(): void
    {
        $this->actingAs($this->buyer)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/rfqs", $this->validRfqPayload(['quantity' => 10]))
            ->assertCreated()
            ->assertJsonPath('body.rfq.below_moq', true);

        $this->assertDatabaseHas('rfqs', ['quantity' => 10]);
    }

    #[Test]
    public function a_product_not_belonging_to_the_inquirys_seller_is_rejected(): void
    {
        $otherProduct = Product::factory()->create();

        $this->actingAs($this->buyer)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/rfqs", $this->validRfqPayload(['product_id' => $otherProduct->id]))
            ->assertStatus(422);
    }

    #[Test]
    public function rfq_creation_is_rate_limited(): void
    {
        config(['inquiries.throttle.create_per_minute' => 1]);

        $this->actingAs($this->buyer)->postJson("/api/v1/inquiries/{$this->inquiry->id}/rfqs", $this->validRfqPayload())->assertCreated();
        $this->actingAs($this->buyer)->postJson("/api/v1/inquiries/{$this->inquiry->id}/rfqs", $this->validRfqPayload())->assertStatus(429);
    }

    #[Test]
    public function an_rfq_is_rejected_when_the_seller_is_past_their_inquiry_limit(): void
    {
        SubscriptionEntitlement::where('key', 'inquiry_limit')->update(['value' => '0']);

        $this->actingAs($this->buyer)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/rfqs", $this->validRfqPayload())
            ->assertStatus(422);

        $this->assertDatabaseCount('rfqs', 0);
    }

    #[Test]
    public function someone_elses_buyer_cannot_submit_an_rfq_on_this_inquiry(): void
    {
        $otherBuyer = User::factory()->wholesaler()->create();

        $this->actingAs($otherBuyer)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/rfqs", $this->validRfqPayload())
            ->assertStatus(403);
    }

    #[Test]
    public function the_addressed_seller_replies_with_a_quotation(): void
    {
        $rfq = Rfq::factory()->create(['inquiry_id' => $this->inquiry->id, 'product_id' => $this->product->id]);

        $this->actingAs($this->sellerUser)
            ->postJson("/api/v1/rfqs/{$rfq->id}/quotations", [
                'price' => 123.45,
                'availability_note' => 'In stock, ready to ship',
                'valid_until' => now()->addWeek()->toDateTimeString(),
            ])
            ->assertCreated()
            ->assertJsonPath('body.quotation.price', '123.45')
            ->assertJsonPath('body.quotation.expired', false);

        $this->assertDatabaseHas('quotations', ['rfq_id' => $rfq->id]);
    }

    #[Test]
    public function a_seller_who_is_not_the_rfqs_target_cannot_quote(): void
    {
        $rfq = Rfq::factory()->create(['inquiry_id' => $this->inquiry->id, 'product_id' => $this->product->id]);

        $otherSeller = User::factory()->importer()->create();
        BusinessAccount::factory()->for($otherSeller, 'owner')->create();

        $this->actingAs($otherSeller)
            ->postJson("/api/v1/rfqs/{$rfq->id}/quotations", [
                'price' => 10,
                'valid_until' => now()->addWeek()->toDateTimeString(),
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function an_expired_quotation_is_shown_as_expired(): void
    {
        $rfq = Rfq::factory()->create(['inquiry_id' => $this->inquiry->id, 'product_id' => $this->product->id]);
        Quotation::factory()->expired()->create(['rfq_id' => $rfq->id]);

        $this->actingAs($this->buyer)
            ->getJson("/api/v1/rfqs/{$rfq->id}")
            ->assertOk()
            ->assertJsonPath('body.rfq.quotations.0.expired', true);
    }

    #[Test]
    public function a_non_party_cannot_view_an_rfq(): void
    {
        $rfq = Rfq::factory()->create(['inquiry_id' => $this->inquiry->id, 'product_id' => $this->product->id]);
        $stranger = User::factory()->wholesaler()->create();

        $this->actingAs($stranger)
            ->getJson("/api/v1/rfqs/{$rfq->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function guests_cannot_use_rfqs_or_quotations(): void
    {
        $rfq = Rfq::factory()->create(['inquiry_id' => $this->inquiry->id, 'product_id' => $this->product->id]);

        $this->getJson("/api/v1/rfqs/{$rfq->id}")->assertStatus(401);
        $this->postJson("/api/v1/rfqs/{$rfq->id}/quotations", [])->assertStatus(401);
    }
}
