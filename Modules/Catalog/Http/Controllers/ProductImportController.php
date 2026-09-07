<?php

namespace Modules\Catalog\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Catalog\Http\Requests\StoreProductImportRequest;
use Modules\Catalog\Http\Resources\ProductImportBatchResource;
use Modules\Catalog\Jobs\ProcessProductImport;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImportBatch;
use Modules\Catalog\Support\ProductCsvTemplate;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

/**
 * Bulk product import (Phase 3.3, US-SEL-09/US-SEL-10). CSV only — an XLSX
 * reader is a follow-up pending dependency sign-off on the issue.
 */
class ProductImportController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    /**
     * The import template — header row + one worked example. CSV, UTF-8 BOM.
     */
    public function template(): Response
    {
        return response(ProductCsvTemplate::toCsv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="thob-product-import-template.csv"',
        ]);
    }

    /**
     * Accept the file, persist it, dispatch the queued job, return the batch id.
     */
    public function store(StoreProductImportRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('create', Product::class);

        $file = $request->file('file');
        $disk = config('catalog.import.disk', 'local');
        $path = $file->store('product-imports', $disk);

        $batch = ProductImportBatch::create([
            'business_account_id' => $user->businessAccount->getKey(),
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'created_by' => $user->getKey(),
        ]);

        ProcessProductImport::dispatch($batch);

        return $this
            ->apiCode(202)
            ->apiMessage(__('catalog::messages.bulk_import_started'))
            ->apiBody(['import' => new ProductImportBatchResource($batch->refresh())])
            ->apiResponse();
    }

    /**
     * The batch status plus the per-row outcome report, owner-scoped.
     */
    public function show(ProductImportBatch $import): JsonResponse
    {
        $this->authorize('view', $import);

        return $this
            ->apiBody(['import' => new ProductImportBatchResource($import->load('rows'))])
            ->apiResponse();
    }
}
