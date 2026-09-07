<?php

namespace Modules\Notifications\Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Events\ProductSubmitted;
use Modules\Catalog\Models\Product;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\Message;
use Modules\Inquiries\Events\InquiryCreated;
use Modules\Inquiries\Events\QuotationReceived;
use Modules\Inquiries\Events\RfqCreated;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Models\Quotation;
use Modules\Inquiries\Models\Rfq;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Models\NotificationPreference;
use Modules\Notifications\Notifications\NewInquiryNotification;
use Modules\Notifications\Notifications\NewMessageNotification;
use Modules\Notifications\Notifications\NewRfqNotification;
use Modules\Notifications\Notifications\ProductSubmittedNotification;
use Modules\Notifications\Notifications\QuotationReceivedNotification;
use Modules\Notifications\Notifications\SubscriptionExpiredNotification;
use Modules\Notifications\Notifications\VerificationDecidedNotification;
use Modules\Notifications\Notifications\VerificationSubmittedNotification;
use Modules\Subscriptions\Events\SubscriptionExpired;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Verification\Events\VerificationApproved;
use Modules\Verification\Events\VerificationSubmitted;
use Modules\Verification\Models\VerificationRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $sellerUser;

    private BusinessAccount $sellerBusiness;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->buyer = User::factory()->wholesaler()->create();
        $this->sellerUser = User::factory()->importer()->create();
        $this->sellerBusiness = BusinessAccount::factory()->for($this->sellerUser, 'owner')->create();

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'inquiry_limit', 'value' => '50']);
        Subscription::create([
            'business_account_id' => $this->sellerBusiness->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }

    private function pushDriver(): string
    {
        return NotificationChannel::Push->driver();
    }

    #[Test]
    public function sending_an_inquiry_notifies_the_seller_owner_on_database_and_push(): void
    {
        $product = Product::factory()->for($this->sellerBusiness, 'businessAccount')->create();

        $this->actingAs($this->buyer)->postJson('/api/v1/inquiries', [
            'product_id' => $product->id,
            'message' => 'Interested.',
        ])->assertCreated();

        Notification::assertSentTo(
            $this->sellerUser,
            NewInquiryNotification::class,
            fn ($notification, array $channels): bool => in_array('database', $channels, true)
                && in_array($this->pushDriver(), $channels, true),
        );

        Notification::assertNotSentTo($this->buyer, NewInquiryNotification::class);
    }

    #[Test]
    public function disabling_the_message_push_preference_drops_push_but_keeps_the_in_app_record(): void
    {
        NotificationPreference::factory()->disabled()->create([
            'user_id' => $this->sellerUser->id,
            'category' => NotificationCategory::Message,
            'channel' => NotificationChannel::Push,
        ]);

        $conversation = Conversation::factory()->create([
            'inquiry_id' => Inquiry::factory()->create([
                'buyer_id' => $this->buyer->id,
                'seller_business_id' => $this->sellerBusiness->id,
            ])->id,
            'buyer_id' => $this->buyer->id,
            'seller_business_id' => $this->sellerBusiness->id,
        ]);
        $message = Message::factory()->for($conversation)->create(['sender_id' => $this->buyer->id]);

        event(new MessageSent($message));

        Notification::assertSentTo(
            $this->sellerUser,
            NewMessageNotification::class,
            fn ($n, array $channels): bool => in_array('database', $channels, true)
                && ! in_array($this->pushDriver(), $channels, true),
        );
    }

    #[Test]
    public function an_operational_subscription_notification_forces_mail_and_sms_even_with_them_disabled(): void
    {
        foreach ([NotificationChannel::Push, NotificationChannel::Mail, NotificationChannel::Sms] as $channel) {
            NotificationPreference::factory()->disabled()->create([
                'user_id' => $this->sellerUser->id,
                'category' => NotificationCategory::Subscription,
                'channel' => $channel,
            ]);
        }

        $subscription = Subscription::query()->where('business_account_id', $this->sellerBusiness->id)->first();
        event(new SubscriptionExpired($subscription));

        Notification::assertSentTo(
            $this->sellerUser,
            SubscriptionExpiredNotification::class,
            fn ($n, array $channels): bool => in_array('mail', $channels, true)
                && in_array(NotificationChannel::Sms->driver(), $channels, true)
                && ! in_array($this->pushDriver(), $channels, true),
        );
    }

    #[Test]
    public function verification_submitted_and_product_submitted_fan_out_to_every_admin_only(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();

        $vr = VerificationRequest::factory()->for($this->sellerBusiness, 'businessAccount')->create();
        event(new VerificationSubmitted($vr));

        Notification::assertSentTo([$adminA, $adminB], VerificationSubmittedNotification::class);
        Notification::assertNotSentTo($this->sellerUser, VerificationSubmittedNotification::class);

        $product = Product::factory()->for($this->sellerBusiness, 'businessAccount')->create();
        event(new ProductSubmitted($product));

        Notification::assertSentTo([$adminA, $adminB], ProductSubmittedNotification::class);
    }

    #[Test]
    public function an_rfq_notifies_the_seller_and_a_quotation_notifies_the_buyer(): void
    {
        $inquiry = Inquiry::factory()->create([
            'buyer_id' => $this->buyer->id,
            'seller_business_id' => $this->sellerBusiness->id,
        ]);
        $rfq = Rfq::factory()->create(['inquiry_id' => $inquiry->id]);

        event(new RfqCreated($rfq));
        Notification::assertSentTo($this->sellerUser, NewRfqNotification::class);
        Notification::assertNotSentTo($this->buyer, NewRfqNotification::class);

        $quotation = Quotation::factory()->create(['rfq_id' => $rfq->id]);
        event(new QuotationReceived($quotation));
        Notification::assertSentTo($this->buyer, QuotationReceivedNotification::class);
        Notification::assertNotSentTo($this->sellerUser, QuotationReceivedNotification::class);
    }

    #[Test]
    public function a_verification_decision_reaches_the_owner_on_all_four_channels(): void
    {
        $vr = VerificationRequest::factory()->for($this->sellerBusiness, 'businessAccount')->create();

        event(new VerificationApproved($vr));

        Notification::assertSentTo(
            $this->sellerUser,
            VerificationDecidedNotification::class,
            fn ($n, array $channels): bool => in_array('database', $channels, true)
                && in_array($this->pushDriver(), $channels, true)
                && in_array('mail', $channels, true)
                && in_array(NotificationChannel::Sms->driver(), $channels, true),
        );
    }

    #[Test]
    public function every_notification_is_queued(): void
    {
        $inquiry = Inquiry::factory()->create([
            'buyer_id' => $this->buyer->id,
            'seller_business_id' => $this->sellerBusiness->id,
        ]);

        event(new InquiryCreated($inquiry));

        Notification::assertSentTo(
            $this->sellerUser,
            NewInquiryNotification::class,
            fn ($notification): bool => $notification instanceof ShouldQueue,
        );
    }
}
