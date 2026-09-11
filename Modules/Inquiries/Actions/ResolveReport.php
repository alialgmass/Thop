<?php

namespace Modules\Inquiries\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Models\AuditLog;
use Modules\Inquiries\Enums\ReportStatus;
use Modules\Inquiries\Exceptions\ReportAlreadyResolvedException;
use Modules\Inquiries\Models\Report;

/**
 * The single place an admin resolves (or dismisses) a report is applied
 * (US-ADM-08, Phase 9 · T9). Shared by the REST endpoint and the Filament
 * panel.
 */
class ResolveReport
{
    public function handle(Report $report, User $admin, string $note, ReportStatus $status = ReportStatus::Resolved): Report
    {
        if ($report->status->isResolved()) {
            throw new ReportAlreadyResolvedException;
        }

        DB::transaction(function () use ($report, $admin, $note, $status): void {
            $report->forceFill([
                'status' => $status,
                'resolved_by' => $admin->getKey(),
                'resolution_note' => $note,
                'resolved_at' => now(),
            ])->save();

            AuditLog::record($admin, AuditAction::ReportResolved, $report, [
                'status' => $status->value,
            ]);
        });

        return $report;
    }
}
