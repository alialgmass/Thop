<?php

namespace Modules\Inquiries\Enums;

use Modules\Inquiries\Models\Report;

/**
 * The dispute-queue workflow state of a {@see Report}
 * (US-ADM-08, Phase 9 · T9). `Investigating` has no dedicated action on
 * either surface — no acceptance criterion asked for one — it exists so the
 * column can hold it if a later ticket adds that transition.
 */
enum ReportStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function isResolved(): bool
    {
        return $this === self::Resolved || $this === self::Dismissed;
    }
}
