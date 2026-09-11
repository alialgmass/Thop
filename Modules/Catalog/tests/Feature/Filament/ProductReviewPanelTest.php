<?php

namespace Modules\Catalog\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Catalog\Database\Factories\ProductMediaFactory;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Events\ProductApproved;
use Modules\Catalog\Events\ProductEditsRequested;
use Modules\Catalog\Events\ProductHidden;
use Modules\Catalog\Events\ProductRejected;
use Modules\Catalog\Filament\Resources\ProductReviews\Pages\ListProductReviews;
use Modules\Catalog\Filament\Resources\ProductReviews\Pages\ViewProductReview;
use Modules\Catalog\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductReviewPanelTest extends TestCase
{
    use RefreshDatabase;

    private function pendingProductWithImage(): Product
    {
        $product = Product::factory()->pendingReview()->create();
        (new ProductMediaFactory)->for($product)->create();

        return $product;
    }

    #[Test]
    public function a_non_admin_cannot_open_the_product_review_panel(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin/product-reviews')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_pending_review_products_in_the_list(): void
    {
        $product = $this->pendingProductWithImage();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListProductReviews::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$product]);
    }

    #[Test]
    public function an_admin_approves_from_the_view_page(): void
    {
        Event::fake([ProductApproved::class]);
        $product = $this->pendingProductWithImage();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ViewProductReview::class, ['record' => $product->getKey()])
            ->callAction('approve');

        $this->assertSame(ProductStatus::Published, $product->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'product.approved',
            'auditable_id' => $product->id,
        ]);
        Event::assertDispatched(ProductApproved::class);
    }

    #[Test]
    public function an_admin_rejects_with_a_reason_from_the_view_page(): void
    {
        Event::fake([ProductRejected::class]);
        $product = $this->pendingProductWithImage();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewProductReview::class, ['record' => $product->getKey()])
            ->callAction('reject', ['reason' => 'Blurry photos.']);

        $product->refresh();
        $this->assertSame(ProductStatus::Rejected, $product->status);
        $this->assertSame('Blurry photos.', $product->rejection_reason);
        Event::assertDispatched(ProductRejected::class);
    }

    #[Test]
    public function reject_requires_a_reason(): void
    {
        $product = $this->pendingProductWithImage();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewProductReview::class, ['record' => $product->getKey()])
            ->callAction('reject', ['reason' => ''])
            ->assertHasActionErrors(['reason' => 'required']);

        $this->assertSame(ProductStatus::PendingReview, $product->refresh()->status);
    }

    #[Test]
    public function an_admin_requests_edits_and_the_product_returns_to_draft(): void
    {
        Event::fake([ProductEditsRequested::class]);
        $product = $this->pendingProductWithImage();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewProductReview::class, ['record' => $product->getKey()])
            ->callAction('requestEdits', ['reason' => 'Add MOQ.']);

        $product->refresh();
        $this->assertSame(ProductStatus::Draft, $product->status);
        $this->assertSame('Add MOQ.', $product->rejection_reason);
        Event::assertDispatched(ProductEditsRequested::class);
    }

    #[Test]
    public function an_admin_hides_a_published_product_from_the_view_page(): void
    {
        Event::fake([ProductHidden::class]);
        $product = Product::factory()->published()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewProductReview::class, ['record' => $product->getKey()])
            ->callAction('hide');

        $this->assertSame(ProductStatus::Hidden, $product->refresh()->status);
        Event::assertDispatched(ProductHidden::class);
    }

    #[Test]
    public function decide_actions_are_hidden_once_a_product_is_resolved(): void
    {
        $product = Product::factory()->rejected()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewProductReview::class, ['record' => $product->getKey()])
            ->assertActionHidden('approve')
            ->assertActionHidden('reject')
            ->assertActionHidden('requestEdits')
            ->assertActionHidden('hide');
    }
}
