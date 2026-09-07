<?php

namespace Modules\Catalog\Policies;

use App\Models\User;
use Modules\Catalog\Models\ProductImportBatch;

/**
 * A seller may only see their own import batches and the per-row report
 * (Phase 3.3 — "result report ... scoped to the owner"). Foreign access = 403,
 * matching {@see ProductPolicy}.
 */
class ProductImportBatchPolicy
{
    public function view(User $user, ProductImportBatch $batch): bool
    {
        return $batch->business_account_id === $user->businessAccount?->getKey();
    }
}
