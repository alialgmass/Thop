<?php

namespace Modules\Search\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Search\Http\Requests\SupplierSearchRequest;
use Modules\Search\Http\Resources\SupplierCardResource;
use Modules\Search\Services\SupplierSearchService;
use Modules\Search\Services\ZeroResultLogger;

/**
 * Public supplier discovery (US-SRC-07). Returns only business accounts —
 * customers have none — filtered by governorate, verification status and
 * specialty, with optional free text over company name / activity.
 */
class SupplierSearchController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SupplierSearchService $service,
        private readonly ZeroResultLogger $zeroResultLogger,
    ) {}

    public function index(SupplierSearchRequest $request): JsonResponse
    {
        $results = $this->service->search($request->searchParams());

        $this->zeroResultLogger->record(
            $request->input('search'),
            $results,
            'supplier',
            $request->user()?->getKey(),
        );

        $payload = SupplierCardResource::collection($results)->toResponse($request)->getData(true);

        return $this
            ->apiBody(['suppliers' => $payload])
            ->apiResponse();
    }
}
