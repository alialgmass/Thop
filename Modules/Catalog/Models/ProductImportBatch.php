<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Database\Factories\ProductImportBatchFactory;
use Modules\Catalog\Enums\ImportBatchStatus;

/**
 * One bulk product upload (Phase 3.3). Owns a `product_import_rows` row per data
 * line so the seller can pull a per-row outcome report.
 *
 * @property int $id
 * @property int $business_account_id
 * @property string $original_filename
 * @property string $stored_path
 * @property ImportBatchStatus $status
 * @property int $total_rows
 * @property int $imported_count
 * @property int $failed_count
 * @property int $limit_rejected_count
 * @property string|null $error
 * @property int|null $created_by
 * @property Carbon|null $completed_at
 */
class ProductImportBatch extends Model
{
    /** @use HasFactory<ProductImportBatchFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ImportBatchStatus::Pending->value,
        'total_rows' => 0,
        'imported_count' => 0,
        'failed_count' => 0,
        'limit_rejected_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportBatchStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ProductImportBatchFactory
    {
        return ProductImportBatchFactory::new();
    }

    /**
     * @return BelongsTo<BusinessAccount, $this>
     */
    public function businessAccount(): BelongsTo
    {
        return $this->belongsTo(BusinessAccount::class);
    }

    /**
     * @return HasMany<ProductImportRow, $this>
     */
    public function rows(): HasMany
    {
        return $this->hasMany(ProductImportRow::class)->orderBy('row_number');
    }
}
