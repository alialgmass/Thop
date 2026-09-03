<?php

namespace Modules\Search\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Http\Resources\ProductCardResource;
use Modules\Catalog\Models\Product;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Search\Http\Requests\ProductSearchRequest;
use Modules\Search\Http\Resources\ProductDetailResource;
use Modules\Search\Services\ProductSearchService;
use Modules\Search\Services\ZeroResultLogger;

/**
 * Public buyer-facing product discovery (US-SRC-01..05). No authentication
 * required — the marketplace is discoverable before sign-in. Visibility is
 * enforced in {@see ProductSearchService} via Product::scopeBuyerVisible
 * (BR-SRC-02); a non-visible product is indistinguishable from a missing one
 * (404, never 403).
 */
class ProductSearchController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductSearchService $service,
        private readonly ZeroResultLogger $zeroResultLogger,
    ) {}

    public function index(ProductSearchRequest $request): JsonResponse
    {
        $results = $this->service->search($request->searchParams());

        $this->zeroResultLogger->record(
            $request->input('search'),
            $results,
            'product',
            $request->user()?->getKey(),
        );

        $payload = ProductCardResource::collection($results)->toResponse($request)->getData(true);

        $message = $results->total() === 0 ? __('search::messages.no_results') : __('search::messages.results');

        return $this
            ->apiMessage($message)
            ->apiBody(['products' => $payload])
            ->apiResponse();
    }

    /**
     * A single supplier's published catalog, with the same filter/sort/paginate
     * contract as global search (US-SRC-06).
     */
    public function supplierCatalog(ProductSearchRequest $request, int $business): JsonResponse
    {
        $results = $this->service->search($request->searchParams(), businessAccountId: $business);

        $payload = ProductCardResource::collection($results)->toResponse($request)->getData(true);

        return $this
            ->apiBody(['products' => $payload])
            ->apiResponse();
    }

    public function show(Request $request, int $product): JsonResponse
    {
        $model = Product::query()
            ->buyerVisible()
            ->with(['fabricType', 'material', 'governorate', 'colors', 'media', 'priceTiers', 'businessAccount.governorate'])
            ->find($product);

        abort_if($model === null, 404);

        return $this
            ->apiBody(['product' => new ProductDetailResource($model)])
            ->apiResponse();
    }
}
