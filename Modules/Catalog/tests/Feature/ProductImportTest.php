<?php

namespace Modules\Catalog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Events\ProductImportCompleted;
use Modules\Catalog\Models\ProductImportBatch;
use Modules\Catalog\Support\ProductCsvTemplate;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\FabricType;
use Modules\Taxonomy\Models\Governorate;
use Modules\Taxonomy\Models\Material;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessAccount $business;

    private int $fabricTypeId;

    private int $materialId;

    private int $governorateId;

    private int $colorId;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->importer()->create();
        $this->business = BusinessAccount::factory()->for($this->user, 'owner')->create();

        $this->fabricTypeId = FabricType::factory()->create()->id;
        $this->materialId = Material::factory()->create()->id;
        $this->governorateId = Governorate::factory()->create()->id;
        $this->colorId = Color::factory()->create()->id;

        $this->plan('100');
    }

    private function plan(string $productLimit): void
    {
        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'product_limit', 'value' => $productLimit]);

        Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function csv(array $rows): string
    {
        $lines = [implode(',', ProductCsvTemplate::HEADERS)];

        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(
                fn (string $header): string => $row[$header] ?? '',
                ProductCsvTemplate::HEADERS,
            ));
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validRow(array $overrides = []): array
    {
        return array_merge([
            'name_ar' => 'قماش قطني',
            'fabric_type_id' => (string) $this->fabricTypeId,
            'material_id' => (string) $this->materialId,
            'governorate_id' => (string) $this->governorateId,
            'unit' => 'per_meter',
            'quantity_available' => '500',
            'price' => '42.50',
            'price_on_contact' => '0',
            'color_ids' => (string) $this->colorId,
        ], $overrides);
    }

    private function upload(string $csv, string $name = 'products.csv'): ProductImportBatch
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/products/import', [
                'file' => UploadedFile::fake()->createWithContent($name, $csv),
            ])
            ->assertStatus(202)
            ->assertJsonPath('body.import.id', fn ($id) => is_int($id));

        // Queue is sync in tests, so the batch is already processed here.
        return ProductImportBatch::latest('id')->firstOrFail()->refresh();
    }

    #[Test]
    public function a_mixed_file_imports_the_good_rows_and_reports_the_bad_one(): void
    {
        $csv = $this->csv([
            $this->validRow(['name_ar' => 'قماش أ']),
            $this->validRow(['name_ar' => '', 'price' => '10']),   // missing name
            $this->validRow(['name_ar' => 'قماش ج', 'price' => '5', 'price_on_contact' => '1']), // price XOR broken
            $this->validRow(['name_ar' => 'قماش د', 'price' => '', 'price_on_contact' => '1']),  // price on contact — ok
        ]);

        $batch = $this->upload($csv);

        $this->assertSame('completed', $batch->status->value);
        $this->assertSame(2, $batch->imported_count);
        $this->assertSame(2, $batch->failed_count);
        $this->assertSame(4, $batch->total_rows);

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', ['name_ar' => 'قماش أ', 'status' => 'pending_review']);
        $this->assertDatabaseHas('products', ['name_ar' => 'قماش د', 'price_on_contact' => true, 'price' => null]);

        // row_number is the physical file line (header = line 1), so the 2nd and
        // 3rd data rows are lines 3 and 4.
        $failed = $batch->rows()->where('status', 'failed')->get();
        $this->assertCount(2, $failed);
        $this->assertArrayHasKey('name_ar', $failed->firstWhere('row_number', 3)->errors);
        $this->assertArrayHasKey('price', $failed->firstWhere('row_number', 4)->errors);
    }

    #[Test]
    public function the_batch_stops_creating_products_once_the_plan_limit_is_reached(): void
    {
        $this->business->subscriptions()->delete();
        $this->plan('2');

        $csv = $this->csv([
            $this->validRow(['name_ar' => 'واحد']),
            $this->validRow(['name_ar' => 'اثنان']),
            $this->validRow(['name_ar' => 'ثلاثة']),
            $this->validRow(['name_ar' => 'أربعة']),
        ]);

        $batch = $this->upload($csv);

        $this->assertSame(2, $batch->imported_count);
        $this->assertSame(2, $batch->limit_rejected_count);
        $this->assertSame(0, $batch->failed_count);
        $this->assertDatabaseCount('products', 2);

        // 3rd and 4th data rows → physical lines 4 and 5.
        $rejected = $batch->rows()->where('status', 'limit_rejected')->get();
        $this->assertEqualsCanonicalizing([4, 5], $rejected->pluck('row_number')->all());

        // US-SEL-10 — the rejected rows carry the upgrade prompt, not a bare skip.
        $this->assertArrayHasKey('product_limit', $rejected->first()->errors);
    }

    #[Test]
    public function the_per_row_report_is_scoped_to_the_owner(): void
    {
        $batch = $this->upload($this->csv([$this->validRow()]));

        $intruder = User::factory()->importer()->create();
        BusinessAccount::factory()->for($intruder, 'owner')->create();

        $this->actingAs($intruder)
            ->getJson("/api/v1/products/import/{$batch->id}")
            ->assertStatus(403);

        $this->actingAs($this->user)
            ->getJson("/api/v1/products/import/{$batch->id}")
            ->assertOk()
            ->assertJsonPath('body.import.imported_count', 1)
            ->assertJsonPath('body.import.rows.0.status', 'imported');
    }

    #[Test]
    public function the_template_download_carries_the_expected_columns(): void
    {
        $response = $this->actingAs($this->user)->get('/api/v1/products/import/template');

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename="thob-product-import-template.csv"');

        $body = $response->content();
        $this->assertStringContainsString('name_ar,name_en,description,fabric_type_id', $body);
        $this->assertStringContainsString('price_on_contact,color_ids,price_tiers', $body);
    }

    #[Test]
    public function a_non_csv_upload_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/products/import', [
                'file' => UploadedFile::fake()->create('catalogue.pdf', 12, 'application/pdf'),
            ])
            ->assertStatus(400)
            ->assertJsonPath('custom_code', 4000);

        $this->assertDatabaseCount('product_import_batches', 0);
    }

    #[Test]
    public function a_wholesaler_cannot_bulk_import(): void
    {
        $wholesaler = User::factory()->wholesaler()->create();
        BusinessAccount::factory()->for($wholesaler, 'owner')->create();

        $this->actingAs($wholesaler)
            ->postJson('/api/v1/products/import', [
                'file' => UploadedFile::fake()->createWithContent('p.csv', $this->csv([$this->validRow()])),
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function an_unreadable_file_fails_the_batch_without_creating_products(): void
    {
        Event::fake([ProductImportCompleted::class]);

        $batch = $this->upload("not,a,matching,header\n1,2,3,4\n", 'junk.csv');

        $this->assertSame('failed', $batch->status->value);
        $this->assertNotNull($batch->error);
        $this->assertDatabaseCount('products', 0);
        Event::assertDispatched(ProductImportCompleted::class);
    }

    #[Test]
    public function a_completed_import_dispatches_the_result_event(): void
    {
        Event::fake([ProductImportCompleted::class]);

        $this->upload($this->csv([$this->validRow()]));

        Event::assertDispatched(ProductImportCompleted::class);
    }

    #[Test]
    public function a_non_numeric_price_is_reported_not_coerced(): void
    {
        $batch = $this->upload($this->csv([
            $this->validRow(['name_ar' => 'سعر غلط', 'price' => 'abc']),
        ]));

        $this->assertSame(1, $batch->failed_count);
        $this->assertSame(0, $batch->imported_count);
        $this->assertArrayHasKey('price', $batch->rows()->sole()->errors);
        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function blank_lines_do_not_inflate_counts_and_row_numbers_track_the_file(): void
    {
        $csv = implode(',', ProductCsvTemplate::HEADERS)."\n"
            ."\n"                                                    // line 2 — blank
            .implode(',', array_map(
                fn (string $h): string => $this->validRow(['name_ar' => 'بعد الفراغ'])[$h] ?? '',
                ProductCsvTemplate::HEADERS,
            ))."\n";                                                 // line 3 — the only data row

        $batch = $this->upload($csv);

        $this->assertSame(1, $batch->total_rows);
        $this->assertSame(1, $batch->imported_count);
        $this->assertSame(3, $batch->rows()->sole()->row_number);
    }
}
