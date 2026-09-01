<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Reports\Actions\GenerateDashboardMetricsAction;

class DashboardController extends Controller
{
    public function __invoke(GenerateDashboardMetricsAction $action): Response
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $metrics = $action->execute($tenantId);

        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
        ]);
    }
}
