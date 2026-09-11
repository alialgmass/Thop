<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Services\LiquidityMetrics;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

/**
 * Marketplace-health dashboard (US-ADM-06, Phase 9 · T7, issue #36). The
 * gate is the `admin` route middleware, matching every other admin-only
 * controller in this phase. Read-only — nothing here writes to the audit
 * log, there is nothing an admin "does" by viewing a dashboard.
 */
class LiquidityDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly LiquidityMetrics $metrics) {}

    public function index(Request $request): JsonResponse
    {
        $days = max(1, (int) $request->query('range', 7));

        return $this
            ->apiBody($this->metrics->summarize($days))
            ->apiResponse();
    }
}
