<?php

namespace Modules\Admin\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Inquiries\Models\Inquiry;
use Modules\Search\Models\SearchLog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin marketplace-health dashboard (US-ADM-06, Phase 9 · T7, issue #36).
 */
class LiquidityDashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_reach_the_dashboard(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->getJson('/api/v1/admin/dashboard/liquidity')
            ->assertForbidden();
    }

    #[Test]
    public function it_counts_active_sellers_and_products_from_buyer_visible_products_only(): void
    {
        $activeSeller = BusinessAccount::factory()->verified()->create();
        Product::factory()->published()->for($activeSeller)->count(2)->create();

        // Draft product: not buyer-visible, so this seller doesn't count.
        $draftOnlySeller = BusinessAccount::factory()->verified()->create();
        Product::factory()->draft()->for($draftOnlySeller)->create();

        // Suspended owner: buyerVisible() excludes the whole business.
        $suspendedOwner = User::factory()->importer()->create(['status' => 'suspended']);
        $suspendedSeller = BusinessAccount::factory()->verified()->for($suspendedOwner, 'owner')->create();
        Product::factory()->published()->for($suspendedSeller)->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/dashboard/liquidity')
            ->assertOk();

        $response->assertJsonPath('body.active_sellers', 1);
        $response->assertJsonPath('body.active_products', 2);
    }

    #[Test]
    public function it_counts_distinct_active_buyers_and_inquiries_within_the_window(): void
    {
        $buyer = User::factory()->wholesaler()->create();
        Inquiry::factory()->for($buyer, 'buyer')->count(2)->create(['created_at' => now()->subDay()]);

        // Outside the default 7-day window.
        Inquiry::factory()->create(['created_at' => now()->subDays(30)]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/dashboard/liquidity')
            ->assertOk();

        $response->assertJsonPath('body.active_buyers', 1);
        $response->assertJsonPath('body.inquiries_last_period', 2);
    }

    #[Test]
    public function range_overrides_the_default_seven_day_window(): void
    {
        Inquiry::factory()->create(['created_at' => now()->subDays(20)]);

        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/dashboard/liquidity')
            ->assertJsonPath('body.inquiries_last_period', 0);

        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/dashboard/liquidity?range=30')
            ->assertJsonPath('body.inquiries_last_period', 1)
            ->assertJsonPath('body.range_days', 30);
    }

    #[Test]
    public function active_sellers_and_products_are_current_state_snapshots_unaffected_by_range(): void
    {
        $business = BusinessAccount::factory()->verified()->create();
        Product::factory()->published()->for($business)->create();
        Inquiry::factory()->create(['created_at' => now()->subDay()]);

        $admin = User::factory()->admin()->create();

        $short = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard/liquidity?range=1')->assertOk();
        $long = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard/liquidity?range=365')->assertOk();

        $short->assertJsonPath('body.active_sellers', 1);
        $long->assertJsonPath('body.active_sellers', 1);
        $short->assertJsonPath('body.active_products', 1);
        $long->assertJsonPath('body.active_products', 1);

        // The windowed metric, by contrast, does change with the range.
        $short->assertJsonPath('body.inquiries_last_period', 1);
    }

    #[Test]
    public function zero_result_terms_are_capped_at_the_top_n(): void
    {
        foreach (range(1, 15) as $i) {
            SearchLog::query()->create([
                'term' => "term-{$i}", 'normalized_term' => "term-{$i}", 'result_count' => 0,
            ]);
        }

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/dashboard/liquidity')
            ->assertOk();

        $this->assertCount(10, $response->json('body.zero_result_terms'));
    }

    #[Test]
    public function zero_result_terms_are_grouped_by_normalized_term_with_no_user_id_exposed(): void
    {
        SearchLog::query()->create(['term' => 'قماش حرير', 'normalized_term' => 'حرير', 'result_count' => 0, 'user_id' => 42]);
        SearchLog::query()->create(['term' => 'حرير طبيعي', 'normalized_term' => 'حرير', 'result_count' => 0, 'user_id' => 7]);
        SearchLog::query()->create(['term' => 'صوف', 'normalized_term' => 'صوف', 'result_count' => 0, 'user_id' => null]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/dashboard/liquidity')
            ->assertOk();

        $response->assertJsonFragment(['term' => 'حرير', 'count' => 2]);
        $response->assertJsonFragment(['term' => 'صوف', 'count' => 1]);
        $response->assertJsonMissing(['user_id' => 42]);
    }
}
