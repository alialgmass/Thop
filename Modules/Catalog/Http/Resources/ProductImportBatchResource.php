<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Models\ProductImportBatch;

/**
 * @mixin ProductImportBatch
 */
class ProductImportBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->original_filename,
            'status' => $this->status->value,
            'total_rows' => $this->total_rows,
            'imported_count' => $this->imported_count,
            'failed_count' => $this->failed_count,
            'limit_rejected_count' => $this->limit_rejected_count,
            'error' => $this->error,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'rows' => $this->whenLoaded('rows', fn () => $this->rows->map(fn ($row): array => [
                'row_number' => $row->row_number,
                'status' => $row->status->value,
                'product_id' => $row->product_id,
                'errors' => $row->errors,
            ])->all()),
        ];
    }
}
