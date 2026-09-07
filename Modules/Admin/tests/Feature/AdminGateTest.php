<?php

namespace Modules\Admin\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The shared `admin` gate on `/api/v1/admin/*` — new routes and the
 * verification/product routes that predate this phase alike.
 */
class AdminGateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_request_to_an_admin_endpoint_is_rejected_with_401(): void
    {
        $this->getJson('/api/v1/admin/audit-logs')
            ->assertStatus(401)
            ->assertJsonPath('custom_code', 4001);
    }

    #[Test]
    public function a_non_admin_is_rejected_with_an_enveloped_403(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->getJson('/api/v1/admin/audit-logs')
            ->assertStatus(403)
            ->assertJsonPath('custom_code', 4031);
    }

    #[Test]
    public function an_admin_is_allowed_through(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/audit-logs')
            ->assertOk();
    }

    #[Test]
    public function the_gate_also_covers_the_pre_existing_product_review_routes(): void
    {
        $this->actingAs(User::factory()->wholesaler()->create())
            ->getJson('/api/v1/admin/products')
            ->assertForbidden();
    }

    #[Test]
    public function the_gate_also_covers_the_pre_existing_verification_routes(): void
    {
        $this->actingAs(User::factory()->wholesaler()->create())
            ->getJson('/api/v1/admin/verification-requests')
            ->assertForbidden();
    }
}
