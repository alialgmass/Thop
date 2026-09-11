<?php

namespace Modules\Admin\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\UserStatus;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin account suspend/reactivate (US-ADM-07, Phase 9 · T8, issue #37).
 */
class AdminAccountModerationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_suspend_an_account(): void
    {
        $target = User::factory()->importer()->create();

        $this->actingAs(User::factory()->importer()->create())
            ->postJson("/api/v1/admin/accounts/{$target->id}/suspend", ['confirm' => true])
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_suspends_an_account_which_revokes_its_tokens_and_is_audited(): void
    {
        $target = User::factory()->importer()->create();
        $token = $target->createToken('api')->plainTextToken;
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/accounts/{$target->id}/suspend", ['confirm' => true])
            ->assertOk();

        $this->assertSame(UserStatus::Suspended, $target->refresh()->status);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'account.suspended', 'auditable_id' => $target->id,
        ]);

        // The old token is now invalid — the suspended user's next request
        // is rejected by Sanctum's own auth middleware, nothing new needed.
        // forgetGuards() clears the guard actingAs() cached the admin onto,
        // so this request actually re-resolves the (now-missing) token
        // instead of reusing the earlier admin session.
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    #[Test]
    public function suspending_without_confirm_changes_nothing(): void
    {
        $target = User::factory()->importer()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/accounts/{$target->id}/suspend")
            ->assertStatus(400);

        $this->assertSame(UserStatus::Active, $target->refresh()->status);
    }

    #[Test]
    public function suspending_an_already_suspended_account_is_rejected(): void
    {
        $target = User::factory()->importer()->create(['status' => UserStatus::Suspended]);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/accounts/{$target->id}/suspend", ['confirm' => true])
            ->assertStatus(409);
    }

    #[Test]
    public function an_admin_cannot_suspend_another_admin(): void
    {
        $target = User::factory()->admin()->create();
        $statusBefore = $target->status;

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/accounts/{$target->id}/suspend", ['confirm' => true])
            ->assertForbidden();

        $this->assertSame($statusBefore, $target->refresh()->status);
    }

    #[Test]
    public function an_admin_reactivates_a_suspended_account_without_restoring_tokens(): void
    {
        $target = User::factory()->importer()->create(['status' => UserStatus::Suspended]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/accounts/{$target->id}/reactivate")
            ->assertOk();

        $this->assertSame(UserStatus::Active, $target->refresh()->status);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'account.reactivated', 'auditable_id' => $target->id,
        ]);
    }

    #[Test]
    public function reactivating_an_account_that_is_not_suspended_is_rejected(): void
    {
        $target = User::factory()->importer()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/accounts/{$target->id}/reactivate")
            ->assertStatus(409);
    }

    #[Test]
    public function a_suspended_sellers_products_disappear_from_search_and_return_on_reactivation(): void
    {
        $owner = User::factory()->importer()->create();
        $business = BusinessAccount::factory()->verified()->for($owner, 'owner')->create();
        $product = Product::factory()->published()->for($business)->create();

        $this->getJson('/api/v1/products')->assertJsonFragment(['id' => $product->id]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->postJson("/api/v1/admin/accounts/{$owner->id}/suspend", ['confirm' => true])
            ->assertOk();

        $this->getJson('/api/v1/products')->assertJsonMissing(['id' => $product->id]);

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/accounts/{$owner->id}/reactivate")
            ->assertOk();

        $this->getJson('/api/v1/products')->assertJsonFragment(['id' => $product->id]);
    }
}
