<?php

namespace Modules\Catalog\Enums;

/**
 * Lifecycle of a bulk product import (Phase 3.3). `pending` on upload, `processing`
 * while the queued job runs, then `completed` once every row has an outcome —
 * or `failed` if the file itself could not be read (not a CSV, no header row).
 */
enum ImportBatchStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
