<?php

namespace Modules\Catalog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Catalog\Actions\ImportProductRows;
use Modules\Catalog\Enums\ImportBatchStatus;
use Modules\Catalog\Models\ProductImportBatch;

/**
 * Runs a bulk product import off the queue (Phase 3.3). The upload endpoint
 * returns immediately with the batch id; the seller polls the batch for the
 * per-row report. All the work lives in {@see ImportProductRows}.
 */
class ProcessProductImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ProductImportBatch $batch) {}

    public function handle(ImportProductRows $import): void
    {
        $import->handle($this->batch);
    }

    public function failed(\Throwable $e): void
    {
        $this->batch->forceFill([
            'status' => ImportBatchStatus::Failed,
            'error' => $e->getMessage(),
            'completed_at' => now(),
        ])->save();
    }
}
