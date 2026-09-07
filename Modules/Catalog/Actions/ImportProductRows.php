<?php

namespace Modules\Catalog\Actions;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Catalog\Enums\ImportBatchStatus;
use Modules\Catalog\Enums\ImportRowStatus;
use Modules\Catalog\Events\ProductImportCompleted;
use Modules\Catalog\Exceptions\ProductLimitExceededException;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImportBatch;
use Modules\Catalog\Support\ProductCsvReader;
use Modules\Catalog\Support\ProductCsvTemplate;
use Modules\Catalog\Support\ProductValidationRules;
use RuntimeException;

/**
 * Processes one bulk product import end to end (Phase 3.3, US-SEL-09/US-SEL-10).
 *
 * Each row is validated independently against {@see ProductValidationRules} —
 * the same source the single-create endpoint uses — and imported through
 * {@see CreateProduct} so the plan product_limit (BR-SEL-01), the review-queue
 * routing (BR-SEL-02) and the usage counter are all enforced exactly once. A
 * bad row is recorded and skipped (spec §4.2); once the limit is hit, every
 * remaining row is reported as limit-rejected with the upgrade prompt and
 * nothing further is created (US-SEL-10).
 */
class ImportProductRows
{
    public function __construct(
        private readonly CreateProduct $createProduct,
    ) {}

    public function handle(ProductImportBatch $batch): void
    {
        $batch->forceFill(['status' => ImportBatchStatus::Processing])->save();

        $path = Storage::disk(config('catalog.import.disk', 'local'))->path($batch->stored_path);

        try {
            $reader = new ProductCsvReader($path);
        } catch (RuntimeException $e) {
            $batch->forceFill([
                'status' => ImportBatchStatus::Failed,
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ])->save();

            ProductImportCompleted::dispatch($batch);

            return;
        }

        $imported = 0;
        $failed = 0;
        $limitRejected = 0;
        $limitReached = false;

        foreach ($reader->rows() as $rowNumber => $raw) {
            if ($limitReached) {
                $this->recordRow($batch, $rowNumber, ImportRowStatus::LimitRejected, errors: $this->upgradePrompt());
                $limitRejected++;

                continue;
            }

            $payload = $this->normalise($raw);
            $validator = $this->rowValidator($payload);

            if ($validator->fails()) {
                $this->recordRow($batch, $rowNumber, ImportRowStatus::Failed, errors: $validator->errors()->toArray());
                $failed++;

                continue;
            }

            try {
                $product = $this->createFromRow($batch, $payload, $validator->validated());
                $this->recordRow($batch, $rowNumber, ImportRowStatus::Imported, productId: $product->getKey());
                $imported++;
            } catch (ProductLimitExceededException $e) {
                $this->recordRow($batch, $rowNumber, ImportRowStatus::LimitRejected, errors: $e->getCustomBody() ?: $this->upgradePrompt());
                $limitRejected++;
                $limitReached = true;
            }
        }

        $batch->forceFill([
            'status' => ImportBatchStatus::Completed,
            'imported_count' => $imported,
            'failed_count' => $failed,
            'limit_rejected_count' => $limitRejected,
            'total_rows' => $imported + $failed + $limitRejected,
            'completed_at' => now(),
        ])->save();

        ProductImportCompleted::dispatch($batch);
    }

    /**
     * Map the flat CSV cells onto the create-product payload shape: compound
     * columns become arrays (grammar owned by {@see ProductCsvTemplate}) and
     * blank cells are dropped so `nullable` rules see "absent" rather than "".
     * `price` / `price_on_contact` are passed through as given so row validation
     * still sees a "both set" row; the XOR is resolved once in
     * {@see self::createFromRow()}.
     *
     * @param  array<string, string>  $raw
     * @return array<string, mixed>
     */
    private function normalise(array $raw): array
    {
        $scalarKeys = [
            'name_ar', 'name_en', 'description', 'fabric_type_id', 'material_id',
            'governorate_id', 'width_cm', 'weight_gsm', 'unit', 'moq', 'quantity_available',
        ];

        $payload = [];

        foreach ($scalarKeys as $key) {
            if (($raw[$key] ?? '') !== '') {
                $payload[$key] = $raw[$key];
            }
        }

        $colorIds = ProductCsvTemplate::parseColorIds($raw['color_ids'] ?? '');
        if ($colorIds !== []) {
            $payload['colors'] = $colorIds;
        }

        $priceTiers = ProductCsvTemplate::parsePriceTiers($raw['price_tiers'] ?? '');
        if ($priceTiers !== []) {
            $payload['price_tiers'] = $priceTiers;
        }

        $payload['price_on_contact'] = filter_var($raw['price_on_contact'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $payload['price'] = ($raw['price'] ?? '') === '' ? null : $raw['price'];

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function rowValidator(array $payload): ValidatorContract
    {
        return Validator::make($payload, ProductValidationRules::fields(partial: false))
            ->after(function (ValidatorContract $validator) use ($payload): void {
                if ($payload['price'] !== null && ! is_numeric($payload['price'])) {
                    $validator->errors()->add('price', __('validation.numeric', ['attribute' => 'price']));

                    return;
                }

                if (! ProductValidationRules::pricingIsValid($payload['price'], $payload['price_on_contact'])) {
                    $validator->errors()->add('price', __('catalog::messages.invalid_pricing'));
                }
            });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     */
    private function createFromRow(ProductImportBatch $batch, array $payload, array $validated): Product
    {
        $attributes = Arr::except($validated, ['colors', 'price_tiers']);
        $attributes['price_on_contact'] = $payload['price_on_contact'];
        $attributes['price'] = $payload['price_on_contact'] ? null : $payload['price'];

        return $this->createProduct->create(
            $batch->businessAccount,
            $attributes,
            colorIds: $validated['colors'] ?? [],
            priceTiers: $validated['price_tiers'] ?? [],
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function upgradePrompt(): array
    {
        return ['product_limit' => [__('catalog::messages.product_limit_exceeded')]];
    }

    /**
     * @param  array<string, list<string>>|null  $errors
     */
    private function recordRow(
        ProductImportBatch $batch,
        int $rowNumber,
        ImportRowStatus $status,
        ?int $productId = null,
        ?array $errors = null,
    ): void {
        $batch->rows()->create([
            'row_number' => $rowNumber,
            'status' => $status,
            'product_id' => $productId,
            'errors' => $errors,
        ]);
    }
}
