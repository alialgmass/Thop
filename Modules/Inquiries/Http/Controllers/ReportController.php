<?php

namespace Modules\Inquiries\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Inquiries\Enums\ReportableType;
use Modules\Inquiries\Http\Requests\StoreReportRequest;
use Modules\Inquiries\Http\Resources\ReportResource;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Models\Report;

/**
 * Either party flagging an inquiry as abusive (US-INQ-09). Only the record
 * is guaranteed here — the Admin dispute/ticket queue that reads it is
 * Phase 9 (US-ADM-08).
 */
class ReportController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function store(StoreReportRequest $request, Inquiry $inquiry): JsonResponse
    {
        $this->authorize('report', $inquiry);

        $report = Report::create([
            'reportable_type' => ReportableType::Inquiry->value,
            'reportable_id' => $inquiry->getKey(),
            'reporter_id' => $request->user()->getKey(),
            'reason' => $request->string('reason')->toString(),
        ]);

        return $this
            ->apiCode(201)
            ->apiMessage(__('inquiries::messages.reported'))
            ->apiBody(['report' => new ReportResource($report)])
            ->apiResponse();
    }
}
