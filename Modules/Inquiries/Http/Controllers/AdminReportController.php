<?php

namespace Modules\Inquiries\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Inquiries\Actions\ResolveReport;
use Modules\Inquiries\Enums\ReportStatus;
use Modules\Inquiries\Http\Requests\ResolveReportRequest;
use Modules\Inquiries\Http\Resources\AdminReportResource;
use Modules\Inquiries\Models\Report;

/**
 * Admin dispute/report queue (US-ADM-08, Phase 9 · T9, issue #38). The gate
 * is the `admin` route middleware, matching every other admin-only
 * controller.
 */
class AdminReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $reports = Report::query()
            ->withPartiesEagerLoaded()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->openFirst()
            ->paginate();

        $payload = AdminReportResource::collection($reports)->toResponse($request)->getData(true);

        return $this
            ->apiBody(['reports' => $payload])
            ->apiResponse();
    }

    public function resolve(ResolveReportRequest $request, Report $report): JsonResponse
    {
        $status = $request->filled('status')
            ? ReportStatus::from($request->string('status')->toString())
            : ReportStatus::Resolved;

        $resolved = app(ResolveReport::class)->handle($report, $request->user(), (string) $request->string('note'), $status);

        return $this
            ->apiMessage('Report resolved.')
            ->apiBody(['report' => new AdminReportResource($resolved)])
            ->apiResponse();
    }
}
