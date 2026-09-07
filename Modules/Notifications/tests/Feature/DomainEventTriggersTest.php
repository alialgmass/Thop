<?php

namespace Modules\Notifications\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Actions\CreateProduct;
use Modules\Catalog\Events\ProductSubmitted;
use Modules\Catalog\Models\Product;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Notifications\ProductSubmittedNotification;
use Modules\Notifications\Notifications\SubscriptionExpiredNotification;
use Modules\Notifications\Notifications\SubscriptionExpiringNotification;
use Modules\Subscriptions\Events\SubscriptionExpired;
use Modules\Subscriptions\Events\SubscriptionExpiring;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DomainEventTriggersTest extends TestCase
{
    use RefreshDatabase;

    private function importerBusinessWithPlan(array $entitlements = []): BusinessAccount
    {
        $owner = User::factory()->importer()->create();
        $business = BusinessAccount::factory()->for($owner, 'owner')->create();

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        foreach ($entitlements + ['product_limit' => '100'] as $key => $value) {
            SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => $key, 'value' => (string) $value]);
        }
        Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        return $business;
    }

    #[Test]
    public function creating_a_product_into_the_review_queue_fires_product_submitted(): void
    {
        Event::fake([ProductSubmitted::class]);
        config(['catalog.review_create' => true]);

        $business = $this->importerBusinessWithPlan();
        $attributes = Product::factory()->for($business, 'businessAccount')->raw();
        unset($attributes['status'], $attributes['business_account_id']);

        app(CreateProduct::class)->create($business, $attributes);

        Event::assertDispatched(ProductSubmitted::class);
    }

    #[Test]
    public function product_submitted_notifies_every_admin(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $product = Product::factory()->create();
        event(new ProductSubmitted($product));

        Notification::assertSentTo($admin, ProductSubmittedNotification::class);
    }

    #[Test]
    public function processing_a_lapsed_subscription_fires_subscription_expired_and_notifies_the_owner(): void
    {
        Notification::fake();

        $business = $this->importerBusinessWithPlan();
        Subscription::query()->where('business_account_id', $business->id)
            ->update(['current_period_end' => now()->subDay()]);

        $this->artisan('subscriptions:process-period-ends')->assertSuccessful();

        Notification::assertSentTo(
            $business->owner,
            SubscriptionExpiredNotification::class,
            fn ($n, array $channels): bool => in_array('mail', $channels, true),
        );
    }

    #[Test]
    public function marking_a_subscription_expired_twice_only_fires_the_event_once(): void
    {
        Event::fake([SubscriptionExpired::class]);

        $business = $this->importerBusinessWithPlan();
        $subscription = Subscription::query()->where('business_account_id', $business->id)->first();

        $subscription->markExpired();
        $subscription->markExpired();

        Event::assertDispatchedTimes(SubscriptionExpired::class, 1);
    }

    #[Test]
    public function the_notify_expiring_command_fires_subscription_expiring_once_per_period(): void
    {
        Event::fake([SubscriptionExpiring::class]);

        $business = $this->importerBusinessWithPlan();
        Subscription::query()->where('business_account_id', $business->id)
            ->update(['current_period_end' => now()->addDays(3)]);

        $this->artisan('subscriptions:notify-expiring')->assertSuccessful();
        $this->artisan('subscriptions:notify-expiring')->assertSuccessful();

        Event::assertDispatchedTimes(SubscriptionExpiring::class, 1);
    }

    #[Test]
    public function subscription_expiring_notification_reaches_the_owner_on_mail_and_sms(): void
    {
        Notification::fake();

        $business = $this->importerBusinessWithPlan();
        $subscription = Subscription::query()->where('business_account_id', $business->id)->first();

        event(new SubscriptionExpiring($subscription));

        Notification::assertSentTo(
            $business->owner,
            SubscriptionExpiringNotification::class,
            fn ($n, array $channels): bool => in_array('mail', $channels, true)
                && in_array(NotificationChannel::Sms->driver(), $channels, true),
        );
    }
}
