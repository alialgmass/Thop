<?php

namespace Modules\Search\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierSearchTest extends TestCase
{
    use RefreshDatabase;

    private function supplier(array $attributes = []): BusinessAccount
    {
        return BusinessAccount::factory()
            ->for(User::factory()->importer(), 'owner')
            ->create($attributes);
    }

    #[Test]
    public function it_filters_by_governorate_and_verification_status(): void
    {
        $cairo = Governorate::factory()->create();
        $wanted = $this->supplier(['governorate_id' => $cairo->id, 'verification_status' => 'verified']);
        $this->supplier(['governorate_id' => $cairo->id, 'verification_status' => 'unverified']);
        $this->supplier(['governorate_id' => Governorate::factory()->create()->id, 'verification_status' => 'verified']);

        $this->getJson('/api/v1/businesses?'.http_build_query([
            'filters' => ['governorate_id' => $cairo->id, 'verification_status' => 'verified'],
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'body.suppliers.data')
            ->assertJsonPath('body.suppliers.data.0.id', $wanted->id)
            ->assertJsonPath('body.suppliers.data.0.verified', true);
    }

    #[Test]
    public function it_matches_specialty_and_free_text_on_company_name(): void
    {
        $wanted = $this->supplier(['company_name' => 'Nile Cotton Traders', 'activity' => 'cotton wholesale']);
        $this->supplier(['company_name' => 'Delta Silk House', 'activity' => 'silk import']);

        $this->getJson('/api/v1/businesses?search=coton')
            ->assertOk()
            ->assertJsonCount(1, 'body.suppliers.data')
            ->assertJsonPath('body.suppliers.data.0.id', $wanted->id);

        $this->getJson('/api/v1/businesses?'.http_build_query(['filters' => ['specialty' => 'cotton']]))
            ->assertOk()
            ->assertJsonCount(1, 'body.suppliers.data')
            ->assertJsonPath('body.suppliers.data.0.id', $wanted->id);
    }

    #[Test]
    public function customers_never_appear_in_supplier_search(): void
    {
        User::factory()->create(['account_type' => 'customer']);
        $this->supplier();

        $this->getJson('/api/v1/businesses')
            ->assertOk()
            ->assertJsonCount(1, 'body.suppliers.data');
    }

    #[Test]
    public function suspended_suppliers_are_excluded(): void
    {
        $this->supplier();
        $banned = $this->supplier();
        $banned->owner->update(['status' => 'suspended']);

        $this->getJson('/api/v1/businesses')
            ->assertOk()
            ->assertJsonCount(1, 'body.suppliers.data');
    }

    #[Test]
    public function a_featured_supplier_is_ranked_first_and_flagged(): void
    {
        $plain = $this->supplier(['company_name' => 'Zzz Plain Co']);

        $featured = $this->supplier(['company_name' => 'Aaa Featured Co']);
        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Pro']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'featured_supplier', 'value' => 'true']);
        Subscription::create([
            'business_account_id' => $featured->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $response = $this->getJson('/api/v1/businesses')->assertOk();

        $this->assertSame($featured->id, $response->json('body.suppliers.data.0.id'));
        $this->assertTrue($response->json('body.suppliers.data.0.featured'));
    }
}
