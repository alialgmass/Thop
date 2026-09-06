<?php

namespace Modules\Catalog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\CatalogConfig;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessAccount $business;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->importer()->create();
        $this->business = BusinessAccount::factory()->for($this->user, 'owner')->create();
        $this->product = Product::factory()->for($this->business)->draft()->create(['price' => 50, 'price_on_contact' => false]);
    }

    private function upload(?UploadedFile $file = null): TestResponse
    {
        return $this->actingAs($this->user)->postJson(
            "/api/v1/products/{$this->product->id}/media",
            ['file' => $file ?? UploadedFile::fake()->image('fabric.jpg', 800, 600)],
        );
    }

    #[Test]
    public function a_seller_uploads_an_image_and_it_lands_on_the_public_disk(): void
    {
        $this->upload()
            ->assertCreated()
            ->assertJsonPath('body.media.type', 'image')
            ->assertJsonPath('body.media.original_name', 'fabric.jpg');

        $media = $this->product->media()->sole();
        Storage::disk('public')->assertExists($media->path);
        $this->assertStringStartsWith("products/{$this->product->id}/", $media->path);
    }

    #[Test]
    public function a_non_image_file_is_rejected_and_nothing_is_written(): void
    {
        $this->upload(UploadedFile::fake()->create('spec.pdf', 40, 'application/pdf'))
            ->assertStatus(400)
            ->assertJsonPath('custom_code', 4000);

        $this->assertDatabaseCount('product_media', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    #[Test]
    public function a_foreign_seller_cannot_upload_to_this_product(): void
    {
        $intruder = User::factory()->importer()->create();
        BusinessAccount::factory()->for($intruder, 'owner')->create();

        $this->actingAs($intruder)
            ->postJson("/api/v1/products/{$this->product->id}/media", ['file' => UploadedFile::fake()->image('x.jpg')])
            ->assertStatus(403);
    }

    #[Test]
    public function uploads_are_capped_per_product(): void
    {
        config()->set('catalog.media.max_per_product', 2);

        $this->upload()->assertCreated();
        $this->upload()->assertCreated();
        $this->upload()->assertStatus(422)->assertJsonPath('custom_code', 4000);

        $this->assertDatabaseCount('product_media', 2);
    }

    #[Test]
    public function the_seller_reorders_images_so_the_cover_changes(): void
    {
        $this->upload();
        $this->upload();
        [$first, $second] = $this->product->media()->orderBy('id')->pluck('id')->all();

        $this->actingAs($this->user)
            ->patchJson("/api/v1/products/{$this->product->id}/media/order", ['media' => [$second, $first]])
            ->assertOk();

        $this->assertSame(0, ProductMedia::find($second)->sort_order);
        $this->assertSame(1, ProductMedia::find($first)->sort_order);
    }

    #[Test]
    public function a_reorder_missing_an_image_id_is_rejected(): void
    {
        $this->upload();
        $this->upload();
        $onlyOne = $this->product->media()->value('id');

        $this->actingAs($this->user)
            ->patchJson("/api/v1/products/{$this->product->id}/media/order", ['media' => [$onlyOne]])
            ->assertStatus(422);
    }

    #[Test]
    public function the_seller_deletes_an_image_and_the_file_is_removed(): void
    {
        $this->upload();
        $media = $this->product->media()->sole();

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/products/{$this->product->id}/media/{$media->id}")
            ->assertOk();

        $this->assertDatabaseCount('product_media', 0);
        Storage::disk('public')->assertMissing($media->path);
    }

    #[Test]
    public function publishing_a_product_with_no_images_is_refused(): void
    {
        $this->actingAs($this->user)
            ->patchJson("/api/v1/products/{$this->product->id}/status", ['status' => 'published'])
            ->assertStatus(422)
            ->assertJsonStructure(['body' => ['media']]);

        $this->assertSame('draft', $this->product->fresh()->status->value);
    }

    #[Test]
    public function publishing_succeeds_once_an_image_is_attached(): void
    {
        CatalogConfig::create(['key' => 'catalog.review_create', 'value' => '0']);
        $this->upload();

        $this->actingAs($this->user)
            ->patchJson("/api/v1/products/{$this->product->id}/status", ['status' => 'published'])
            ->assertOk()
            ->assertJsonPath('body.product.status', 'published');
    }

    #[Test]
    public function an_admin_cannot_approve_a_pending_product_that_has_no_images(): void
    {
        $admin = User::factory()->admin()->create();
        $this->product->forceFill(['status' => 'pending_review'])->save();

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/products/{$this->product->id}/approve")
            ->assertStatus(422);

        $this->assertSame('pending_review', $this->product->fresh()->status->value);
    }
}
