<?php

namespace Modules\Catalog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Actions\DecideProductReview;
use Modules\Catalog\Database\Factories\ProductMediaFactory;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Events\ProductApproved;
use Modules\Catalog\Events\ProductEditsRequested;
use Modules\Catalog\Events\ProductHidden;
use Modules\Catalog\Events\ProductRejected;
use Modules\Catalog\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin review queue (Phase 9 · T2, US-SEL-11, BR-ADM-01). REST parity with
 * the Filament panel — both surfaces call {@see DecideProductReview}.
 */
class AdminProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function pendingProductWithImage(): Product
    {
        $product = Product::factory()->pendingReview()->create();
        (new ProductMediaFactory)->for($product)->create();

        return $product;
    }

    #[Test]
    public function a_non_admin_cannot_reach_the_review_queue(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->getJson('/api/v1/admin/products')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_pending_review_products_in_the_queue(): void
    {
        $pending = $this->pendingProductWithImage();
        Product::factory()->published()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/products')
            ->assertOk()
            ->assertJsonFragment(['id' => $pending->id])
            ->assertJsonCount(1, 'body.products.data');
    }

    #[Test]
    public function an_admin_approves_a_pending_product(): void
    {
        Event::fake([ProductApproved::class]);
        $product = $this->pendingProductWithImage();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/products/{$product->id}/approve")
            ->assertOk();

        $this->assertSame(ProductStatus::Published, $product->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'product.approved',
            'auditable_id' => $product->id,
        ]);
        Event::assertDispatched(ProductApproved::class);
    }

    #[Test]
    public function approving_a_product_with_no_image_is_rejected(): void
    {
        $product = Product::factory()->pendingReview()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/products/{$product->id}/approve")
            ->assertStatus(422);

        $this->assertSame(ProductStatus::PendingReview, $product->refresh()->status);
    }

    #[Test]
    public function an_admin_rejects_a_pending_product_with_a_reason(): void
    {
        Event::fake([ProductRejected::class]);
        $product = $this->pendingProductWithImage();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/products/{$product->id}/reject", ['reason' => 'Blurry photos.'])
            ->assertOk();

        $product->refresh();
        $this->assertSame(ProductStatus::Rejected, $product->status);
        $this->assertSame('Blurry photos.', $product->rejection_reason);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'product.rejected',
            'auditable_id' => $product->id,
        ]);
        Event::assertDispatched(ProductRejected::class);
    }

    #[Test]
    public function reject_requires_a_reason(): void
    {
        $product = $this->pendingProductWithImage();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/products/{$product->id}/reject", ['reason' => ''])
            ->assertStatus(400);

        $this->assertSame(ProductStatus::PendingReview, $product->refresh()->status);
    }

    #[Test]
    public function an_admin_requests_edits_and_the_product_returns_to_draft(): void
    {
        Event::fake([ProductEditsRequested::class]);
        $product = $this->pendingProductWithImage();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/products/{$product->id}/request-edits", ['reason' => 'Add MOQ before resubmitting.'])
            ->assertOk();

        $product->refresh();
        $this->assertSame(ProductStatus::Draft, $product->status);
        $this->assertSame('Add MOQ before resubmitting.', $product->rejection_reason);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'product.edits_requested',
            'auditable_id' => $product->id,
        ]);
        Event::assertDispatched(ProductEditsRequested::class);
    }

    #[Test]
    public function an_admin_hides_a_published_product(): void
    {
        Event::fake([ProductHidden::class]);
        $product = Product::factory()->published()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/products/{$product->id}/hide")
            ->assertOk();

        $this->assertSame(ProductStatus::Hidden, $product->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'product.hidden',
            'auditable_id' => $product->id,
        ]);
        Event::assertDispatched(ProductHidden::class);
    }

    #[Test]
    public function approving_a_non_pending_product_is_rejected_with_a_409(): void
    {
        $product = Product::factory()->published()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/products/{$product->id}/approve")
            ->assertStatus(409);
    }

    #[Test]
    public function hiding_a_non_published_product_is_rejected_with_a_409(): void
    {
        $product = Product::factory()->draft()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/products/{$product->id}/hide")
            ->assertStatus(409);
    }

    #[Test]
    public function a_non_admin_cannot_approve_reject_hide_or_request_edits(): void
    {
        $product = $this->pendingProductWithImage();
        $seller = User::factory()->importer()->create();

        $this->actingAs($seller)->postJson("/api/v1/admin/products/{$product->id}/approve")->assertForbidden();
        $this->actingAs($seller)->postJson("/api/v1/admin/products/{$product->id}/reject", ['reason' => 'x'])->assertForbidden();
        $this->actingAs($seller)->postJson("/api/v1/admin/products/{$product->id}/request-edits", ['reason' => 'x'])->assertForbidden();
        $this->actingAs($seller)->postJson("/api/v1/admin/products/{$product->id}/hide")->assertForbidden();
    }
}
